import itertools

import cv2
import numpy as np

from config import CLAHE_CLIP
from preprocessing import _to_gray


# Ordena 4 pontos em [top-left, top-right, bottom-right, bottom-left]
def _order_corners(points: np.ndarray) -> np.ndarray:
    points = points.reshape(4, 2)
    sums = points.sum(axis=1)
    diffs = np.diff(points, axis=1)[:, 0]

    top_left = points[np.argmin(sums)]
    bottom_right = points[np.argmax(sums)]
    top_right = points[np.argmin(diffs)]
    bottom_left = points[np.argmax(diffs)]

    return np.array([top_left, top_right, bottom_right, bottom_left], dtype=np.float32)


# Valida se 4 cantos formam uma página plausível
def _valid_quad(points: np.ndarray, height: int, width: int) -> bool:
    pts = points.reshape(4, 2)

    min_dist = max(height, width) * 0.02
    for i in range(4):
        for j in range(i + 1, 4):
            if np.linalg.norm(pts[i] - pts[j]) < min_dist:
                return False

    contour = pts.reshape(-1, 1, 2).astype(np.float32)
    if not cv2.isContourConvex(contour):
        return False

    tl, tr, br, bl = pts
    width_avg = (np.linalg.norm(tr - tl) + np.linalg.norm(br - bl)) / 2
    height_avg = (np.linalg.norm(bl - tl) + np.linalg.norm(br - tr)) / 2
    if width_avg == 0 or not (0.8 < height_avg / width_avg < 2.5):
        return False

    return True


# Acha os 4 fiduciais pretos dos cantos da página
def find_markers(img: np.ndarray) -> np.ndarray | None:
    gray = _to_gray(img)
    gray = cv2.createCLAHE(clipLimit=CLAHE_CLIP, tileGridSize=(8, 8)).apply(gray)
    height, width = gray.shape[:2]

    quad = _find_markers_c(gray, height, width, 15)
    if quad is not None and _not_degenerate(quad):
        return quad

    # Se achou um quadrilátero degenerado, tenta um limiar mais sensível
    if quad is not None:
        quad = _find_markers_c(gray, height, width, 10)
        if quad is not None and _not_degenerate(quad):
            return quad

    return None


# Encontra o melhor quadrilátero de fiduciais com um limiar adaptativo `c`
def _find_markers_c(gray: np.ndarray, height: int, width: int, c: int) -> np.ndarray | None:
    binary = cv2.adaptiveThreshold(
        cv2.GaussianBlur(gray, (5, 5), 0),
        255,
        cv2.ADAPTIVE_THRESH_MEAN_C,
        cv2.THRESH_BINARY_INV,
        51,
        c,
    )

    contours, _ = cv2.findContours(binary, cv2.RETR_LIST, cv2.CHAIN_APPROX_SIMPLE)
    min_area = height * width * 0.00001
    max_area = height * width * 0.02

    candidates: list[tuple[float, float, float]] = []
    for contour in contours:
        area = cv2.contourArea(contour)
        if not (min_area < area < max_area):
            continue

        x, y, w, h = cv2.boundingRect(contour)
        if w == 0 or h == 0:
            continue
        if not (0.5 < w / h < 2.0):
            continue
        if area / (w * h) < 0.5:
            continue

        moments = cv2.moments(contour)
        cx = moments["m10"] / moments["m00"] if moments["m00"] else x + w / 2.0
        cy = moments["m01"] / moments["m00"] if moments["m00"] else y + h / 2.0

        candidates.append((area, cx, cy))

    if len(candidates) < 4:
        return None

    candidates.sort(key=lambda c: c[0], reverse=True)

    best_markers = None
    best_area = 0.0
    for combo in itertools.combinations(candidates[:12], 4):
        centers = np.array([[cx, cy] for _, cx, cy in combo], dtype=np.float32)
        ordered = _order_corners(centers)
        if not _valid_quad(ordered, height, width):
            continue

        quad_area = abs(cv2.contourArea(ordered.reshape(-1, 1, 2)))
        if quad_area > best_area:
            best_area = quad_area
            best_markers = ordered

    return best_markers


# Rejeita quadriláteros degenerados (cantos errados ou lados com tamanhos muito diferentes)
def _not_degenerate(markers: np.ndarray) -> bool:
    tl, tr, br, bl = markers
    top_width = np.linalg.norm(tr - tl)
    bottom_width = np.linalg.norm(br - bl)
    left_height = np.linalg.norm(bl - tl)
    right_height = np.linalg.norm(br - tr)

    if bottom_width == 0 or right_height == 0:
        return False

    if not (0.4 < top_width / bottom_width < 2.5 and 0.4 < left_height / right_height < 2.5):
        return False

    # bordas superior/inferior quase horizontais e laterais quase verticais
    page_width = max(top_width, bottom_width)
    page_height = max(left_height, right_height)

    top_tilt = abs(tl[1] - tr[1])
    bottom_tilt = abs(bl[1] - br[1])
    left_tilt = abs(tl[0] - bl[0])
    right_tilt = abs(tr[0] - br[0])

    if top_tilt > page_height * 0.12 or bottom_tilt > page_height * 0.12:
        return False
    if left_tilt > page_width * 0.12 or right_tilt > page_width * 0.12:
        return False

    return True


# Devolve a homografia (raw -> template) pelos fiduciais ou pelo contorno da folha
def get_homography(sub: np.ndarray, tpl: np.ndarray) -> np.ndarray | None:
    sub_markers = find_markers(sub)
    if sub_markers is not None:
        tpl_markers = find_markers(tpl)
        if tpl_markers is not None:
            homography, _ = cv2.findHomography(sub_markers, tpl_markers, cv2.RANSAC, 5.0)
            if homography is not None:
                return homography

    gray = _to_gray(sub)
    gray = cv2.createCLAHE(clipLimit=CLAHE_CLIP, tileGridSize=(8, 8)).apply(gray)

    _, binary = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    binary = cv2.morphologyEx(binary, cv2.MORPH_CLOSE, np.ones((15, 15), np.uint8))

    contours, _ = cv2.findContours(binary, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    height, width = gray.shape[:2]

    for contour in sorted(contours, key=cv2.contourArea, reverse=True)[:3]:
        perimeter = cv2.arcLength(contour, True)
        approx = cv2.approxPolyDP(contour, 0.02 * perimeter, True)
        if len(approx) != 4:
            continue
        if cv2.contourArea(contour) < height * width * 0.3:
            continue

        destination = np.float32(
            [[0, 0], [tpl.shape[1], 0], [tpl.shape[1], tpl.shape[0]], [0, tpl.shape[0]]]
        )
        return cv2.getPerspectiveTransform(_order_corners(approx), destination)

    return None
