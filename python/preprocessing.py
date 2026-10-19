import cv2
import numpy as np
import pymupdf as fitz

from config import (
    ADAPTIVE_BLOCK,
    ADAPTIVE_C,
    CLAHE_CLIP,
    DPI,
    OMR_DARK_FACTOR,
    OMR_MIN_RADIUS,
)


# Rasteriza cada página de um arquivo (PDF/imagem) em BGR
def render_pages(path: str, dpi: int = DPI) -> list[np.ndarray]:
    doc = fitz.open(path)
    pages = []
    try:
        for page in doc:
            pixmap = page.get_pixmap(dpi=dpi)
            img = np.frombuffer(pixmap.samples, dtype=np.uint8).reshape(
                pixmap.height, pixmap.width, pixmap.n
            )

            if pixmap.n == 4:
                img = cv2.cvtColor(img, cv2.COLOR_RGBA2BGR)
            elif pixmap.n == 3:
                img = cv2.cvtColor(img, cv2.COLOR_RGB2BGR)
            pages.append(img)
    finally:
        doc.close()

    return pages


# Recorta uma região normalizada da página
def crop_region(page: np.ndarray, region: dict) -> np.ndarray:
    h, w = page.shape[:2]
    x = int(region["x"] * w)
    y = int(region["y"] * h)
    x2 = min(x + int(region["width"] * w), w)
    y2 = min(y + int(region["height"] * h), h)
    return page[y:y2, x:x2]


# Converte BGR para escala de cinza
def _to_gray(img: np.ndarray) -> np.ndarray:
    return cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)


# Binariza a imagem (tinta = branco)
def _binarize(gray: np.ndarray) -> np.ndarray:
    clahe = cv2.createCLAHE(clipLimit=CLAHE_CLIP, tileGridSize=(8, 8))
    gray = clahe.apply(gray)
    return cv2.adaptiveThreshold(
        gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY_INV, ADAPTIVE_BLOCK, ADAPTIVE_C
    )


# Subtrai o template impresso, isolando a caligrafia
def difference_binary(sub_crop: np.ndarray, tpl_crop: np.ndarray | None) -> np.ndarray:
    sub_bin = _binarize(_to_gray(sub_crop))

    if tpl_crop is None:
        return sub_bin

    tpl_bin = _binarize(_to_gray(tpl_crop))
    tpl_bin = cv2.dilate(tpl_bin, np.ones((3, 3), np.uint8), iterations=1)

    return cv2.bitwise_and(sub_bin, cv2.bitwise_not(tpl_bin))


# Fração de pixels com tinta numa imagem binária
def density(binary: np.ndarray | None) -> float:
    if binary is None:
        return 0.0
    return float(np.count_nonzero(binary) / binary.size)


# Fração de pixels escuros no interior circular de uma bolha (cx, cy, r em px)
def bubble_ink(gray: np.ndarray, cx: float, cy: float, r: float) -> float:
    # brilho do papel local (mediana da vizinhança da bolha)
    px0 = max(0, int(cx - 3 * r))
    px1 = min(gray.shape[1], int(cx + 3 * r))
    py0 = max(0, int(cy - 3 * r))
    py1 = min(gray.shape[0], int(cy + 3 * r))
    paper = float(np.median(gray[py0:py1, px0:px1]))
    if paper <= 0:
        return 0.0

    # recorta a região interna e aplica uma máscara circular (descarta o contorno)
    inset = 0.25
    inner_r = r * (1 - inset)
    x0 = max(0, int(cx - inner_r))
    x1 = min(gray.shape[1], int(cx + inner_r))
    y0 = max(0, int(cy - inner_r))
    y1 = min(gray.shape[0], int(cy + inner_r))

    crop = gray[y0:y1, x0:x1]
    if crop.size == 0:
        return 0.0

    yy, xx = np.ogrid[: crop.shape[0], : crop.shape[1]]
    dist = np.sqrt((xx - (cx - x0)) ** 2 + (yy - (cy - y0)) ** 2)
    interior = crop[dist <= inner_r]

    if interior.size == 0:
        return 0.0

    return float(np.count_nonzero(interior < paper * OMR_DARK_FACTOR) / interior.size)


# Detecta bolhas (círculos) numa janela da página e devolve (cx, cy, r)
def detect_bubbles(
    gray: np.ndarray,
    y0: int,
    y1: int,
    x0: int,
    x1: int,
) -> list[tuple[float, float, float]]:
    x0 = max(0, x0)
    x1 = min(gray.shape[1], x1)
    strip = cv2.medianBlur(gray[max(0, y0) : min(gray.shape[0], y1), x0:x1], 5)
    circles = cv2.HoughCircles(
        strip,
        cv2.HOUGH_GRADIENT,
        dp=1.2,
        minDist=25,
        param1=80,
        param2=20,
        minRadius=14,
        maxRadius=32,
    )
    if circles is None:
        return []

    return [
        (float(c[0]) + x0, float(c[1]) + max(0, y0), float(c[2]))
        for c in circles[0]
        if c[2] > OMR_MIN_RADIUS
    ]


# Expande a região para capturar a caligrafia completa
def _expand_region(region: dict, vertical: float, horizontal: float) -> dict:
    new_x = max(0.0, region["x"] - region["width"] * horizontal)
    new_y = max(0.0, region["y"] - region["height"] * vertical)

    return {
        **region,
        "x": new_x,
        "y": new_y,
        "width": min(region["width"] * (1 + 2 * horizontal), 1.0 - new_x),
        "height": min(region["height"] * (1 + 2 * vertical), 1.0 - new_y),
    }
