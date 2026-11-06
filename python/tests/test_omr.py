import cv2
import numpy as np
import pytest

from config import OMR_MARK_DENSITY
from pipeline import select_choices
from preprocessing import bubble_ink, crop_region, density, detect_bubbles


def _filled_circle(size: int, cx: int, cy: int, r: int) -> np.ndarray:
    page = np.full((size, size), 255, dtype=np.uint8)
    cv2.circle(page, (cx, cy), r, 0, -1)
    return page


def _ring(size: int, cx: int, cy: int, r: int, thickness: int = 2) -> np.ndarray:
    page = np.full((size, size), 255, dtype=np.uint8)
    cv2.circle(page, (cx, cy), r, 0, thickness)
    return page


def test_bubble_ink_filled_is_above_threshold():
    page = _filled_circle(300, 150, 150, 20)

    ink = bubble_ink(page, 150, 150, 22)

    assert ink > OMR_MARK_DENSITY


def test_bubble_ink_empty_ring_is_below_threshold():
    ring = _ring(300, 150, 150, 22, 2)

    assert bubble_ink(ring, 150, 150, 22) < OMR_MARK_DENSITY


def test_bubble_ink_empty_ring_below_filled():
    filled = _filled_circle(300, 150, 150, 20)
    ring = _ring(300, 150, 150, 22, 2)

    assert bubble_ink(ring, 150, 150, 22) < bubble_ink(filled, 150, 150, 22)


def test_bubble_ink_blank_is_zero():
    page = np.full((300, 300), 255, dtype=np.uint8)

    assert bubble_ink(page, 150, 150, 20) == 0.0


def test_bubble_ink_dark_paper_does_not_fake_ink():
    page = np.full((300, 300), 120, dtype=np.uint8)

    assert bubble_ink(page, 150, 150, 20) == 0.0


def test_detect_bubbles_finds_circles():
    page = np.full((600, 600), 255, dtype=np.uint8)
    for cy in (100, 200, 300):
        cv2.circle(page, (100, cy), 20, 0, 2)

    bubbles = detect_bubbles(page, 0, 600, 0, 300)

    assert len(bubbles) == 3


def test_detect_bubbles_ignores_small_circles():
    page = np.full((600, 600), 255, dtype=np.uint8)
    cv2.circle(page, (100, 100), 20, 0, 2)  # bolha
    cv2.circle(page, (100, 200), 10, 0, -1)  # marca pequena (r=10)

    bubbles = detect_bubbles(page, 0, 600, 0, 300)

    assert len(bubbles) == 1


def test_detect_bubbles_respects_x_window():
    page = np.full((600, 600), 255, dtype=np.uint8)
    cv2.circle(page, (100, 100), 20, 0, 2)  # bolha (dentro da janela)
    cv2.circle(page, (500, 200), 20, 0, 2)  # bolha (fora da janela)

    bubbles = detect_bubbles(page, 0, 600, 50, 150)

    assert len(bubbles) == 1
    assert abs(bubbles[0][0] - 100) < 5


def test_detect_bubbles_blank_is_empty():
    page = np.full((600, 600), 255, dtype=np.uint8)

    assert detect_bubbles(page, 0, 600, 0, 300) == []


def test_crop_region_bounds():
    page = np.arange(100).reshape(10, 10).astype(np.uint8)

    crop = crop_region(page, {"x": 0.2, "y": 0.2, "width": 0.4, "height": 0.4})

    assert crop.shape == (4, 4)


def test_density_counts_ink():
    binary = np.zeros((10, 10), dtype=np.uint8)
    binary[0:5, :] = 255

    assert density(binary) == 0.5


def test_density_none_is_zero():
    assert density(None) == 0.0


@pytest.mark.parametrize(
    ("densities", "expected"),
    [
        ([0.9, 0.0, 0.8, 0.0, 0.7], [0, 2, 4]),
        ([0.0, 0.0, 0.9, 0.0], [2]),
        ([0.0, 0.0, 0.0, 0.0], []),
        ([0.9, 0.0, 0.95, 0.0], [0, 2]),
    ],
)
def test_select_choices(densities, expected):
    assert select_choices(densities, OMR_MARK_DENSITY) == expected


def test_select_choices_returns_all_marked():
    # aluno marcou duas opções: todas devem ser retornadas, sem preferir a mais forte
    assert select_choices([0.6, 0.9, 0.05, 0.0], OMR_MARK_DENSITY) == [0, 1]
