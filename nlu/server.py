"""AI 助手的 NLU 旁挂服务。

与 htweb **同机**运行，只监听回环地址。htweb 通过 HTTP 调它：

    POST /infer    {"text": "把早读挪到9点"}
    → {"intent":"move_schedule","confidence":0.97,
       "slots":{"time":["9点"],"content":["早读"]},
       "tokens":[...], "tags":[...]}

    GET /healthz   → {"status":"ok","model":"...","device":"cpu"}

# 为什么单独一个进程

模型是 PyTorch 的（joint_rbt3，147MB 权重），htweb 是纯 Go 单二进制、
直接依赖只有两个，把 PyTorch 塞进去既不可能也不划算。拆开之后边界很干净：
**这一侧只做推理，另一侧做全部业务**。

# 为什么用标准库的 http.server 而不是 FastAPI

它只有两个接口、只服务本机的一个调用方、并发量等于人打字的速度。
标准库够用，还能少装 fastapi + uvicorn 两大坨依赖 ——
这台机器上那个 venv 已经 7GB 了。

# 只听回环

默认绑 127.0.0.1。这个服务没有任何鉴权，**绝不能对外暴露**：
它虽然不写数据，但能被人拿去无限次跑推理，也会泄露意图体系。
鉴权在 htweb 那一侧做（助手接口要登录、每个意图按权限位卡）。
"""
from __future__ import annotations

import argparse
import json
import logging
import os
import sys
import threading
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

from engine import Engine, EngineConfig  # noqa: E402

LOG = logging.getLogger("nlu")

# 推理不是线程安全的（PyTorch 模块共享状态），而 ThreadingHTTPServer 会并发进来。
# 加一把锁串起来：单次推理 100ms 级，人打字的速度排不满，串行完全够用。
_INFER_LOCK = threading.Lock()
_ENGINE: Engine | None = None

# 请求体上限。一句语音指令撑死几百字节，收到 64KB 说明对面不是我们的调用方。
MAX_BODY = 64 * 1024


class Handler(BaseHTTPRequestHandler):
    protocol_version = "HTTP/1.1"

    # 默认实现会把每条请求打到 stderr，量大且没用，静音掉
    def log_message(self, fmt, *args):  # noqa: A003
        LOG.debug("%s - %s", self.address_string(), fmt % args)

    def _send(self, code: int, payload: dict) -> None:
        body = json.dumps(payload, ensure_ascii=False).encode("utf-8")
        self.send_response(code)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_GET(self) -> None:  # noqa: N802
        if self.path.rstrip("/") in ("/healthz", "/health"):
            if _ENGINE is None:
                self._send(503, {"status": "loading"})
                return
            self._send(200, {
                "status": "ok",
                "device": _ENGINE.cfg.device,
                "model": str(_ENGINE.cfg.model_dir),
                "intents": len(_ENGINE.id2intent),
                "slotLabels": len(_ENGINE.slot_labels),
                "threshold": _ENGINE.cfg.confidence_threshold,
            })
            return
        self._send(404, {"error": "not found"})

    def do_POST(self) -> None:  # noqa: N802
        if self.path.rstrip("/") != "/infer":
            self._send(404, {"error": "not found"})
            return
        if _ENGINE is None:
            self._send(503, {"error": "模型尚未加载完成"})
            return

        try:
            length = int(self.headers.get("Content-Length") or 0)
        except ValueError:
            self._send(400, {"error": "Content-Length 不合法"})
            return
        if length <= 0 or length > MAX_BODY:
            self._send(400, {"error": "请求体为空或过大"})
            return

        try:
            payload = json.loads(self.rfile.read(length).decode("utf-8"))
        except (UnicodeDecodeError, json.JSONDecodeError) as exc:
            self._send(400, {"error": f"请求体不是合法 JSON: {exc}"})
            return

        text = (payload or {}).get("text") or ""
        if not isinstance(text, str) or not text.strip():
            self._send(400, {"error": "text 不能为空"})
            return

        try:
            with _INFER_LOCK:
                intent, conf, slots, tokens, tags = _ENGINE.infer(text.strip())
        except Exception as exc:  # noqa: BLE001 - 推理失败要如实回报，不能静默
            LOG.exception("推理失败")
            self._send(500, {"error": f"推理失败: {exc}"})
            return

        self._send(200, {
            "intent": intent,
            "confidence": round(conf, 6),
            "slots": slots,
            "tokens": tokens,
            "tags": tags,
        })


def main() -> int:
    ap = argparse.ArgumentParser(description="AI 助手 NLU 服务")
    ap.add_argument("--model-dir", default=os.getenv("NLU_MODEL_DIR", "./models/joint_rbt3"),
                    help="模型目录，需含 joint_model.pt 与 label_config.json")
    ap.add_argument("--host", default=os.getenv("NLU_HOST", "127.0.0.1"),
                    help="监听地址。⚠ 默认只听回环，不要改成 0.0.0.0")
    ap.add_argument("--port", type=int, default=int(os.getenv("NLU_PORT", "5013")))
    ap.add_argument("--threshold", type=float, default=float(os.getenv("NLU_THRESHOLD", "0.5")),
                    help="意图置信度阈值，低于它判 none")
    ap.add_argument("--device", default=os.getenv("NLU_DEVICE", ""), help="cpu / cuda，留空自动")
    ap.add_argument("--verbose", action="store_true")
    args = ap.parse_args()

    logging.basicConfig(
        level=logging.DEBUG if args.verbose else logging.INFO,
        format="%(asctime)s %(levelname)s %(message)s",
    )

    model_dir = Path(args.model_dir).expanduser().resolve()
    if not model_dir.is_dir():
        LOG.error("模型目录不存在: %s", model_dir)
        return 2

    cfg = EngineConfig(model_dir=model_dir, confidence_threshold=args.threshold)
    if args.device:
        cfg.device = args.device

    LOG.info("正在加载模型 %s (device=%s) …", model_dir, cfg.device)
    global _ENGINE
    try:
        _ENGINE = Engine.load(cfg)
    except Exception as exc:  # noqa: BLE001
        LOG.error("模型加载失败: %s", exc)
        return 3
    LOG.info("模型就绪：%d 个意图、%d 个槽位标签，阈值 %.2f",
             len(_ENGINE.id2intent), len(_ENGINE.slot_labels), cfg.confidence_threshold)

    if args.host not in ("127.0.0.1", "localhost", "::1"):
        LOG.warning("⚠ 监听在 %s —— 这个服务没有鉴权，不应对外暴露", args.host)

    srv = ThreadingHTTPServer((args.host, args.port), Handler)
    LOG.info("NLU 服务已启动: http://%s:%d", args.host, args.port)
    try:
        srv.serve_forever()
    except KeyboardInterrupt:
        LOG.info("收到中断，退出")
    finally:
        srv.server_close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
