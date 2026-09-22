<?php

namespace App\Support\Pdf;

interface PdfRenderer
{
    /**
     * Render the given HTML document and return the raw PDF binary.
     */
    public function generate(string $html): string;

    /**
     * Render the given HTML document and return the page-relative coordinates
     * of every `.answer-region` element,
     * normalized to the page size (0.0–1.0).
     *
     * @return array{
     *     regions: list<array{question_id: int|null, key: string|null, page: int, x: float, y: float, width: float, height: float}>,
     * }
     */
    public function measure(string $html): array;
}
