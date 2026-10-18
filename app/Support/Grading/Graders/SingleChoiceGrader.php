<?php

namespace App\Support\Grading\Graders;

use App\Models\Question;
use App\Models\SubmissionAnswer;

final class SingleChoiceGrader implements Grader
{
    use ChoiceGrading;

    /**
     * @return array{0: float|null, 1: string|null, 2: bool, 3: array<string, mixed>}
     */
    public function grade(Question $question, SubmissionAnswer $answer): array
    {
        $metadata = ['method' => 'deterministic'];

        $correct = $question->correctOptionLetters();
        $selected = $this->selectedLetters($answer);

        if ($selected === []) {
            return [0, 'Resposta em branco.', false, $metadata];
        }

        sort($selected);
        sort($correct);

        if ($selected === $correct) {
            return [$question->weight, 'Resposta correta.', false, $metadata];
        }

        return [0, 'Resposta incorreta.', false, $metadata];
    }
}
