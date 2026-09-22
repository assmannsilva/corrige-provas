<?php

namespace App\Renderers;

use App\Models\Question;

class EssayRenderer implements QuestionRendererInterface
{
    public function renderHtml(Question $question): string
    {
        return '';
    }

    public function renderPdf(Question $question): string
    {
        $lines = $question->metadata['lines'] ?? 10;
        $rows = '';

        for ($i = 0; $i < $lines; $i++) {
            $rows .= '<div style="border-bottom:0.2px solid #000;height:18px;"></div>';
        }

        return <<<HTML
        <div class="answer-region" data-region-key="essay">
            {$rows}
        </div>
        HTML;
    }
}
