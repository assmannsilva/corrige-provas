<?php

namespace App\Renderers;

use App\Models\Question;

interface QuestionRendererInterface
{
    public function renderHtml(Question $question): string;

    public function renderPdf(Question $question): string;
}
