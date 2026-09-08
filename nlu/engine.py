"""joint_rbt3 推理内核。

从 ai_speaker_project 的 src/engine.py 与 src/trainer.py 里**只把推理这条路**
裁出来：模型定义、权重加载、一次前向、BIO 解码。

刻意**没有**搬过来的东西：
  - 训练 / 评估 / 语料处理
  - 槽位后处理里的名称模糊匹配（原实现拿 all_audio.json 之类的缓存做匹配）
    —— 那需要知道系统里有哪些方案、媒体、终端，属于业务，现在归 Go 侧做，
    在那边直接查数据库，比对着一份可能过期的 JSON 匹配准得多。

所以这个进程的职责只有一句话：**文本进，intent + 槽位出。**
它不连数据库、不知道广播系统的存在、不做任何写操作。
"""
from __future__ import annotations

import json
from dataclasses import dataclass
from pathlib import Path
from typing import Dict, List, Optional, Sequence, Tuple

import torch
from torch import nn
from transformers import AutoModel, AutoTokenizer

# torchcrf 装了就用 CRF 解码，没装退回逐位 argmax。
# ⚠ 训练时用的是哪种，推理就必须用哪种 —— 不一致会让槽位边界系统性偏移。
# 权重文件里带了标记（见 load 时的校验）。
try:  # pragma: no cover - 取决于部署环境装没装
    from torchcrf import CRF
except ImportError:  # pragma: no cover
    try:
        from TorchCRF import CRF
    except ImportError:
        CRF = None


@dataclass
class EngineConfig:
    model_dir: Path
    max_length: int = 128
    device: str = "cuda" if torch.cuda.is_available() else "cpu"
    # 低于这个置信度就判 none（听不懂）。
    # ⚠ 阈值是模型的一部分，只在这里定一处 —— Go 侧不再自己判一次，
    #   两处各定一个迟早会对不上。
    confidence_threshold: float = 0.5


class JointRBT3Model(nn.Module):
    """与训练时同构的模型：一个 encoder 上挂意图头和槽位头。

    结构必须与 src/trainer.py 里那个逐字一致，否则 load_state_dict 会对不上。
    这里去掉了所有只在训练时用到的分支（loss、label_smoothing 等）。
    """

    def __init__(self, model_name: str, num_intents: int, num_slot_labels: int, dropout: float = 0.1):
        super().__init__()
        self.encoder = AutoModel.from_pretrained(model_name)
        hidden = self.encoder.config.hidden_size
        self.dropout = nn.Dropout(dropout)
        self.intent_classifier = nn.Linear(hidden, num_intents)
        self.slot_classifier = nn.Linear(hidden, num_slot_labels)
        self.crf = CRF(num_slot_labels, batch_first=True) if CRF else None

    def forward(self, input_ids, attention_mask, token_type_ids=None):
        enc_out = self.encoder(
            input_ids=input_ids,
            attention_mask=attention_mask,
            token_type_ids=token_type_ids,
            return_dict=True,
        )
        seq_output = self.dropout(enc_out.last_hidden_state)
        pooled = self.dropout(
            enc_out.pooler_output if enc_out.pooler_output is not None else seq_output[:, 0]
        )
        intent_logits = self.intent_classifier(pooled)
        slot_logits = self.slot_classifier(seq_output)
        decoded = None
        if self.crf is not None:
            decoded = self.crf.decode(slot_logits, mask=attention_mask.bool())
        return {"intent_logits": intent_logits, "slot_logits": slot_logits, "slot_tags": decoded}


def decode_entities(
    tokens: Sequence[str], tag_ids: Sequence[int], label_list: Sequence[str]
) -> Dict[str, List[str]]:
    """BIO 标签序列 → {槽位名: [值, ...]}。

    与原实现逐行一致。B- 开头或槽位名变了就切一段，O 收尾。
    """
    entities: Dict[str, List[str]] = {}
    current_tokens: List[str] = []
    current_label: Optional[str] = None
    for token, tag_id in zip(tokens, tag_ids):
        label = label_list[tag_id] if 0 <= tag_id < len(label_list) else "O"
        if label == "O":
            if current_label:
                entities.setdefault(current_label, []).append("".join(current_tokens))
            current_tokens = []
            current_label = None
            continue
        prefix, slot = label.split("-", 1)
        if prefix == "B" or slot != current_label:
            if current_label:
                entities.setdefault(current_label, []).append("".join(current_tokens))
            current_tokens = [token]
            current_label = slot
        else:
            current_tokens.append(token)
    if current_label:
        entities.setdefault(current_label, []).append("".join(current_tokens))
    return entities


class Engine:
    def __init__(self, cfg: EngineConfig, tokenizer, model, label_cfg: dict):
        self.cfg = cfg
        self.tokenizer = tokenizer
        self.model = model
        self.id2intent = {int(v): k for k, v in label_cfg["intent2id"].items()}
        self.slot_labels = label_cfg["slot_labels"]

    @classmethod
    def load(cls, cfg: EngineConfig) -> "Engine":
        label_path = cfg.model_dir / "label_config.json"
        if not label_path.exists():
            raise FileNotFoundError(f"缺少标签配置 {label_path}")
        label_cfg = json.loads(label_path.read_text(encoding="utf-8"))

        tokenizer = AutoTokenizer.from_pretrained(str(cfg.model_dir), use_fast=True)
        model = JointRBT3Model(
            str(cfg.model_dir),
            num_intents=len(label_cfg["intent2id"]),
            num_slot_labels=len(label_cfg["slot_labels"]),
        )
        state = torch.load(cfg.model_dir / "joint_model.pt", map_location=cfg.device)
        sd = state["model_state_dict"] if "model_state_dict" in state else state

        # ⚠ 训练用了 CRF、推理没装 torchcrf（或反过来）会让槽位系统性偏移，
        # 而且不报错 —— 只是抽出来的槽位悄悄变差。这里显式对一下。
        trained_with_crf = any(k.startswith("crf.") for k in sd)
        if trained_with_crf and model.crf is None:
            raise RuntimeError(
                "这份权重是带 CRF 训练的，但当前环境没装 torchcrf。"
                "请 pip install pytorch-crf，否则槽位抽取会系统性偏移。"
            )
        model.load_state_dict(sd, strict=not trained_with_crf or model.crf is not None)
        model.to(cfg.device)
        model.eval()
        return cls(cfg, tokenizer, model, label_cfg)

    def _align_tags(self, full_tags: Sequence[int], word_ids: Sequence[Optional[int]], n: int) -> List[int]:
        """subword 级标签对回字符级：每个 word 取第一个 subword 的标签。"""
        aligned: List[int] = []
        prev = None
        for idx, wid in enumerate(word_ids):
            if wid is None:
                continue
            if wid != prev:
                aligned.append(int(full_tags[idx]))
                prev = wid
        return aligned[:n]

    def infer(self, text: str) -> Tuple[str, float, Dict[str, List[str]], List[str], List[int]]:
        """跑一次推理，返回 (intent, confidence, slots, tokens, tags)。

        ⚠ **按字切分**（tokens = list(text)），不是按词 —— 与训练时一致。
        改成按词会让槽位边界全错。
        """
        tokens = list(text)
        enc = self.tokenizer(
            tokens,
            is_split_into_words=True,
            max_length=self.cfg.max_length,
            padding="max_length",
            truncation=True,
            return_tensors="pt",
        )
        word_ids = enc.word_ids()
        inputs = {k: v.to(self.cfg.device) for k, v in enc.items()}

        with torch.no_grad():
            out = self.model(
                input_ids=inputs["input_ids"],
                attention_mask=inputs["attention_mask"],
                token_type_ids=inputs.get("token_type_ids"),
            )

        intent_logits = out["intent_logits"][0]
        probs = torch.softmax(intent_logits, dim=-1)
        intent_id = int(probs.argmax(dim=-1).item())
        conf = float(probs[intent_id].item())
        intent = self.id2intent.get(intent_id, "none")
        if conf < self.cfg.confidence_threshold:
            intent = "none"

        if out.get("slot_tags"):
            full_tags = out["slot_tags"][0]
        else:
            full_tags = out["slot_logits"][0].argmax(dim=-1).tolist()
        tags = self._align_tags(full_tags, word_ids, len(tokens))
        slots = decode_entities(tokens, tags, self.slot_labels)
        return intent, conf, slots, tokens, tags
