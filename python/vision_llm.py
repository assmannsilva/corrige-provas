from __future__ import annotations

import base64
import json
import os
import re
import unicodedata
import urllib.error
import urllib.request
from collections import Counter

import cv2
import numpy as np

OPENROUTER_URL = "https://openrouter.ai/api/v1/chat/completions"

# Legibilidade 1 (ilegível) a 5 (perfeitamente legível); apenas 1 é tratado como incerto
LEGIBILITY_UNCERTAIN_THRESHOLD = 1


# Lê a OPENROUTER_API_KEY do ambiente
def _api_key() -> str:
    key = os.environ.get("OPENROUTER_API_KEY", "").strip()
    if not key:
        raise RuntimeError("OPENROUTER_API_KEY environment variable is not set")
    return key


# Codifica a imagem em base64 (png)
def _encode_image(img: np.ndarray) -> str:
    ok, buf = cv2.imencode(".png", img)
    if not ok:
        raise RuntimeError("Failed to encode image")
    return base64.b64encode(buf.tobytes()).decode("ascii")


# Interpreta a resposta do LLM como (texto, legibilidade), com fallback para texto puro
def _parse_transcription(raw: str) -> tuple[str, int]:
    match = re.search(r"\{.*\}", raw, re.DOTALL)
    if match:
        try:
            data = json.loads(match.group(0))
            if isinstance(data, dict) and "text" in data:
                text = str(data.get("text", "")).strip()
                legibility = data.get("legibility", 5)
                if isinstance(legibility, str):
                    legibility = int(legibility.strip()) if legibility.strip().isdigit() else 5
                else:
                    legibility = int(legibility)
                return text, legibility
        except (json.JSONDecodeError, ValueError, TypeError):
            pass

    return raw.strip(), 5


# Normaliza o texto para comparação de concordância entre transcrições
def _normalize_text(text: str) -> str:
    text = text.lower()
    text = unicodedata.normalize("NFKD", text)
    text = "".join(c for c in text if not unicodedata.combining(c))
    return re.sub(r"[^a-z0-9]", "", text)


# Escolhe a transcrição mais legível do grupo majoritário, se houver.
# Sem maioria, escolhe a mais legível de todas e sinaliza revisão.
def _select_consistent(samples: list[tuple[str, int]]) -> tuple[str, bool]:
    texts = [s[0] for s in samples]
    legibilities = [s[1] for s in samples]
    normalized = [_normalize_text(t) for t in texts]

    counts = Counter(normalized)
    majority = counts.most_common(1)[0][0] if counts else None
    has_majority = any(count >= 2 for count in counts.values())

    # Incerteza por discordância: sem consenso (todas as amostras distintas)
    disagree = not has_majority

    uncertain = disagree or any(leg <= LEGIBILITY_UNCERTAIN_THRESHOLD for leg in legibilities)

    if has_majority:
        candidates = [i for i, value in enumerate(normalized) if value == majority]
    else:
        candidates = list(range(len(samples)))

    best_index = max(candidates, key=lambda i: (legibilities[i], len(texts[i])))

    return texts[best_index], uncertain


# Transcreve um recorte de resposta via OpenRouter e devolve (texto, legibilidade).
# O chamador deve recortar apenas a região de resposta antes de chamar esta função.
def transcribe(
    img: np.ndarray,
    user: str = "Transcreva exatamente o texto manuscrito desta imagem, preservando as palavras.",
    model: str | None = None,
    temperature: float = 0.0,
) -> tuple[str, int]:
    model = model or os.environ.get("OPENROUTER_MODEL") or "google/gemini-3.7-flash"

    json_instruction = (
        "Responda somente com um objeto JSON no formato "
        '{"text": "<texto transcrito>", "legibility": <1 a 5>}. '
        "legibility: 1 = ilegível (precisei adivinhar a maioria das palavras), "
        "3 = parcialmente legível (adivinhei algumas palavras), "
        "5 = perfeitamente legível. "
        "Se você adivinhou qualquer palavra, use no máximo 2."
    )

    content: list[dict] = [
        {"type": "text", "text": user + "\n\n" + json_instruction},
        {"type": "image_url", "image_url": {"url": f"data:image/png;base64,{_encode_image(img)}"}},
    ]

    messages: list[dict] = [{"role": "user", "content": content}]

    body = json.dumps({"model": model, "messages": messages, "temperature": temperature}).encode("utf-8")

    request = urllib.request.Request(
        OPENROUTER_URL,
        data=body,
        headers={
            "Content-Type": "application/json",
            "Authorization": "Bearer " + _api_key(),
        },
    )

    try:
        with urllib.request.urlopen(request, timeout=120) as response:
            payload = json.load(response)
    except urllib.error.HTTPError as exc:
        detail = exc.read().decode("utf-8", errors="replace")
        raise RuntimeError(f"OpenRouter request failed ({exc.code}): {detail}") from exc

    content_out = payload.get("choices", [{}])[0].get("message", {}).get("content")

    if isinstance(content_out, list):
        content_out = "".join(
            part.get("text", "") for part in content_out if isinstance(part, dict)
        )

    return _parse_transcription((content_out or "").strip())


# Faz N chamadas e devolve (texto, incerto) por consenso e legibilidade.
def transcribe_consistent(
    img: np.ndarray,
    user: str = "Transcreva exatamente o texto manuscrito desta imagem, preservando as palavras.",
    model: str | None = None,
    n: int = 3,
) -> tuple[str, bool]:
    samples = [transcribe(img, user, model, temperature=0.7) for _ in range(n)]

    return _select_consistent(samples)
