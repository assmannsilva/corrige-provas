<?php

namespace App\Renderers;

use App\Models\Question;

class MultipleChoiceRenderer implements QuestionRendererInterface
{
    public function renderHtml(Question $question): string
    {
        $options = $question->metadata['options'] ?? [];
        $items = '';

        foreach ($options as $index => $option) {
            /** @var int<0, 25> $index */
            $letter = chr(65 + $index);
            $text = is_array($option) ? ($option['text'] ?? '') : $option;
            $items .= <<<HTML
            <label class="flex items-center gap-3 p-2 rounded hover:bg-gray-50 cursor-pointer">
                <span class="flex items-center justify-center w-8 h-8 rounded border-2 border-gray-300 text-sm font-medium text-gray-600">{$letter}</span>
                <span class="text-sm">{$text}</span>
            </label>
            HTML;
        }

        return <<<HTML
        <div>
            <p class="text-xs text-gray-500 mb-2">Marque todas que se aplicam.</p>
            <div class="multiple-choice-options space-y-1">
                {$items}
            </div>
        </div>
        HTML;
    }

    public function renderPdf(Question $question): string
    {
        $options = $question->metadata['options'] ?? [];
        $items = '';

        foreach ($options as $index => $option) {
            $text = is_array($option) ? ($option['text'] ?? '') : $option;
            $items .= '<div style="padding:4px 0;">'
                .'<span class="answer-region" data-region-key="choice:'.$index.'" style="display:inline-block;width:12px;height:12px;border:1px solid #000;border-radius:50%;margin-right:8px;vertical-align:middle;"></span>'
                .'<span style="font-size:11px;">'.e($text).'</span>'
                .'</div>';
        }

        return $items;
    }
}
