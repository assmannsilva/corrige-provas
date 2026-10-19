import os
import re

import cv2
import numpy as np

from alignment import get_homography
from config import BLANK_INK_DENSITY, OMR_MARK_DENSITY, STORAGE_ROOT
from preprocessing import _to_gray, bubble_ink, crop_region, detect_bubbles, density, difference_binary, render_pages
from vision_llm import transcribe_consistent


# Sanitiza a key para uso em nome de arquivo
def _sanitize(value: str) -> str:
    return re.sub(r"[^a-zA-Z0-9_-]", "_", value)


# Seleciona os índices marcados com base na densidade de tinta
def select_choices(densities: list[float], threshold: float) -> list[int]:
    return [i for i, d in enumerate(densities) if d > threshold]


# 'failed' se alguma região falhou, senão 'extracted'
def worst_status(statuses: list[str]) -> str:
    if "failed" in statuses:
        return "failed"
    return "extracted"


class Pipeline:
    # Inicializa o pipeline com os dados da submissão
    def __init__(
        self,
        files: list[str],
        template: str | None,
        questions: list[dict],
        submission_id: int | None = None,
    ):
        self.files = files
        self.template = template
        self.questions = questions
        self.submission_id = submission_id
        self.sub_pages: list[np.ndarray] = []
        self.aligned_pages: list[np.ndarray] = []
        self.homographies: list[np.ndarray | None] = []
        self.tpl_pages: list[np.ndarray] = []

    # Resolve o path absoluto no storage ou lança FileNotFoundError
    def resolve_path(self, path: str) -> str:
        full = os.path.join(STORAGE_ROOT, path)
        if not os.path.exists(full):
            raise FileNotFoundError(f"File not found: {full}")
        return full

    # Renderiza e alinha localmente; somente regiões de respostas seguem para transcrição
    def run(self) -> dict:
        self.sub_pages = []
        for path in self.files:
            self.sub_pages.extend(render_pages(self.resolve_path(path)))

        self.tpl_pages = render_pages(self.resolve_path(self.template)) if self.template else []
        self._align_pages()

        return {
            "results": [self.process_question(q) for q in self.questions],
        }

    # Alinha cada página da submissão com o template (guarda a homografia)
    def _align_pages(self) -> None:
        self.aligned_pages = list(self.sub_pages)
        self.homographies = []

        if not self.tpl_pages:
            return

        for i in range(min(len(self.sub_pages), len(self.tpl_pages))):
            homography = get_homography(self.sub_pages[i], self.tpl_pages[i])
            self.homographies.append(homography)
            if homography is not None:
                height, width = self.tpl_pages[i].shape[:2]
                self.aligned_pages[i] = cv2.warpPerspective(self.sub_pages[i], homography, (width, height))

    # Devolve sub/tpl de uma página (ou None)
    def _page(self, idx: int) -> tuple[np.ndarray | None, np.ndarray | None]:
        sub = self.aligned_pages[idx] if idx < len(self.aligned_pages) else None
        tpl = self.tpl_pages[idx] if idx < len(self.tpl_pages) else None
        return sub, tpl

    # Extrai todas as regiões de uma questão e agrega
    def process_question(self, question: dict) -> dict:
        regions = question.get("regions", [])
        etype = regions[0].get("extraction_type", "handwritten_text") if regions else "handwritten_text"

        if etype == "choice":
            details = self._extract_choice(regions)
        else:
            details = [self.extract_region(question["id"], region) for region in regions]

        return self.aggregate(question["id"], regions, details)

    # Extrai as bolhas de uma questão de escolha (detecta bolhas e, se faltar, mede na posição esperada)
    def _extract_choice(self, regions: list[dict]) -> list[dict]:
        page_idx = int(regions[0].get("page", 1)) - 1
        raw = self.sub_pages[page_idx] if page_idx < len(self.sub_pages) else None
        tpl = self.tpl_pages[page_idx] if page_idx < len(self.tpl_pages) else None
        homography = self.homographies[page_idx] if page_idx < len(self.homographies) else None

        if raw is None or tpl is None or homography is None:
            return [self._extract_choice_region(region) for region in regions]

        gray = _to_gray(raw)
        th, tw = tpl.shape[:2]
        inverse = np.linalg.inv(homography)

        expected: list[tuple[float, float, float]] = []
        for region in regions:
            cx_t = (region["x"] + region["width"] / 2) * tw
            cy_t = (region["y"] + region["height"] / 2) * th
            rx_t = (region["x"] + region["width"]) * tw

            center = cv2.perspectiveTransform(np.array([[[cx_t, cy_t]]], dtype=np.float32), inverse).reshape(2)
            right = cv2.perspectiveTransform(np.array([[[rx_t, cy_t]]], dtype=np.float32), inverse).reshape(2)

            radius = float(np.linalg.norm(right - center))
            expected.append((float(center[0]), float(center[1]), radius))

        expected_x = sum(e[0] for e in expected) / len(expected)
        expected_y = [e[1] for e in expected]

        bubbles = detect_bubbles(
            gray,
            int(min(expected_y) - 250),
            int(max(expected_y) + 250),
            int(expected_x - 60),
            int(expected_x + 60),
        )
        bubbles.sort(key=lambda b: b[1])

        densities = []
        for ex, ey, radius in expected:
            if bubbles:
                nearest = min(bubbles, key=lambda b: abs(b[1] - ey))
                if abs(nearest[1] - ey) < 60:
                    densities.append(bubble_ink(gray, nearest[0], nearest[1], nearest[2]))
                else:
                    densities.append(bubble_ink(gray, ex, ey, radius))
            else:
                densities.append(bubble_ink(gray, ex, ey, radius))

        return [{"key": r["key"], "density": densities[i]} for i, r in enumerate(regions)]

    # Extrai uma bolha individual pela região alinhada (fallback)
    def _extract_choice_region(self, region: dict) -> dict:
        key = region.get("key")
        page_idx = int(region.get("page", 1)) - 1
        sub, _ = self._page(page_idx)
        if sub is None:
            return {"key": key, "density": 0.0}

        gray = _to_gray(sub)
        cx = (region["x"] + region["width"] / 2) * gray.shape[1]
        cy = (region["y"] + region["height"] / 2) * gray.shape[0]
        r = region["width"] * gray.shape[1] / 2
        return {"key": key, "density": bubble_ink(gray, cx, cy, r)}

    # Salva o recorte no disco e devolve o path relativo
    def _save_crop(self, question_id: int, key: str | None, img: np.ndarray) -> str | None:
        if not self.submission_id:
            return None

        rel_dir = f"submissions/{self.submission_id}/crops"
        os.makedirs(os.path.join(STORAGE_ROOT, rel_dir), exist_ok=True)

        rel_path = f"{rel_dir}/{question_id}_{_sanitize(key or 'region')}.png"
        cv2.imwrite(os.path.join(STORAGE_ROOT, rel_path), img)

        return rel_path

    # Extrai uma região de texto (LLM)
    def extract_region(self, question_id: int, region: dict) -> dict:
        key = region.get("key")
        page_idx = int(region.get("page", 1)) - 1

        sub, tpl = self._page(page_idx)
        if sub is None:
            return {"key": key, "status": "failed", "text": "", "density": 0.0}

        crop_path = self._save_crop(question_id, key, crop_region(sub, region))

        # Não expande o recorte: o limite publicado evita incluir o cabeçalho.
        source = region
        sub_crop = crop_region(sub, source)
        tpl_crop = crop_region(tpl, source) if tpl is not None else None
        ink = density(difference_binary(sub_crop, tpl_crop))

        if ink < BLANK_INK_DENSITY:
            return {"key": key, "status": "extracted", "text": "", "density": ink, "crop": crop_path}

        text, uncertain = transcribe_consistent(sub_crop)

        return {"key": key, "status": "extracted", "text": text.strip(), "density": ink, "crop": crop_path, "uncertain": uncertain}

    # Agrega as regiões numa resposta única por questão
    def aggregate(self, question_id: int, regions: list[dict], details: list[dict]) -> dict:
        etype = regions[0].get("extraction_type", "handwritten_text") if regions else "handwritten_text"

        if etype == "choice":
            densities = [float(d.get("density") or 0.0) for d in details]
            selected_indices = select_choices(densities, OMR_MARK_DENSITY)

            selected = [chr(65 + i) for i in selected_indices]

            raw_text = ",".join(selected) if selected else "UNCHECKED"
            metadata = [
                {"key": d["key"], "selected": i in selected_indices, "density": d.get("density")}
                for i, d in enumerate(details)
            ]
            return {
                "question_id": question_id,
                "raw_text": raw_text,
                "status": "extracted",
                "metadata": metadata,
            }

        texts = [d.get("text", "") for d in details]
        statuses = [d.get("status", "failed") for d in details]
        crop = details[0].get("crop") if details else None
        metadata = [
            {"key": d["key"], "text": d.get("text"), "status": d.get("status"), "density": d.get("density"), "crop": d.get("crop")}
            for d in details
        ]

        return {
            "question_id": question_id,
            "raw_text": " | ".join(t for t in texts if t),
            "status": worst_status(statuses),
            "crop": crop,
            "metadata": metadata,
            "uncertain": any(d.get("uncertain", False) for d in details),
        }
