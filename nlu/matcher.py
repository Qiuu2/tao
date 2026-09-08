"""名称模糊匹配。

# 为什么这一层留在 Python 侧

原实现用 rapidfuzz 的 `process.extractOne`（默认打分器是 WRatio —— ratio、
partial_ratio、token_sort、token_set 按长度加权的复合算法）。在 Go 里重写 WRatio
是另一个工程，而且一旦有偏差就是"匹配结果悄悄变了"，很难测出来。

所以分工是：**候选名单由 Go 从数据库查（新鲜、权威），打分交给这里用同一个库做**。
结果与原实现逐位一致，Go 侧不必碰打分算法。

⚠ 这一层同样**不连数据库**：候选名单是调用方传进来的。
"""
from __future__ import annotations

from typing import List, Optional, Tuple

try:  # pragma: no cover - 取决于部署环境
    from rapidfuzz import process as fuzz_process
except ImportError:  # pragma: no cover
    fuzz_process = None


def available() -> bool:
    """rapidfuzz 装没装。

    没装时原实现的行为是**整个模糊层失效**（_fuzzy_match_name 直接返回 None），
    只剩精确匹配与原文包含那两层。这里如实上报，让 Go 侧走同样的降级路径，
    而不是自己另找一个打分器 —— 那会让两套部署给出不同结果。
    """
    return fuzz_process is not None


def match_one(value: str, candidates: List[str], cutoff: float = 60.0) -> Tuple[Optional[str], float]:
    """与原实现的 _fuzzy_match_name 逐行一致。

    返回 (匹配到的名字, 0~1 的分数)；没到 cutoff 就是 (None, 0.0)。
    """
    if not fuzz_process or not candidates or not value:
        return None, 0.0
    res = fuzz_process.extractOne(value, candidates, score_cutoff=cutoff)
    if res:
        choice, score, _ = res
        return choice, score / 100.0
    return None, 0.0
