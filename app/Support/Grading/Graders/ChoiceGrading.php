<?php

namespace App\Support\Grading\Graders;

use App\Models\SubmissionAnswer;

trait ChoiceGrading
{
    /**
     * @return list<string>
     */
    protected function selectedLetters(SubmissionAnswer $answer): array
    {
        $upper = strtoupper(trim((string) $answer->raw_text));

        if ($upper === '' || $upper === 'UNCHECKED') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $upper))));
    }

    protected function choiceFeedback(int $hits, int $misses, int $correctCount): string
    {
        if ($misses === 0 && $hits === $correctCount) {
            return 'Resposta correta.';
        }

        return "{$hits} correta(s), {$misses} errada(s).";
    }
}
