<?php

namespace App\Support\Grading\Graders;

use App\Models\Question;
use App\Models\SubmissionAnswer;

final class MultipleChoiceGrader implements Grader
{
    use ChoiceGrading;

    /**
     * @return array{0: float|null, 1: string|null, 2: bool, 3: array<string, mixed>}
     */
    public function grade(Question $question, SubmissionAnswer $answer): array
    {
        $metadata = ['method' => 'deterministic'];

        $correctAnswers = $question->correctOptionLetters();
        $wrongAnswers = $question->wrongOptionLetters();
        $selected = $this->selectedLetters($answer);

        if ($selected === []) {
            return [0, 'Resposta em branco.', false, $metadata];
        }

        $correctCount = count($correctAnswers);
        $wrongCount = count($wrongAnswers);

        if ($correctCount === 0) {
            return [0, 'Questão sem alternativa correta.', false, $metadata];
        }

        $hits = count(array_intersect($selected, $correctAnswers));
        $misses = count(array_intersect($selected, $wrongAnswers));

        $correctFraction = $hits / $correctCount;
        $wrongFraction = $wrongCount > 0 ? $misses / $wrongCount : 0.0;

        $fraction = max(0.0, $correctFraction - $wrongFraction);

        $score = round($question->weight * $fraction, 2);

        return [$score, $this->choiceFeedback($hits, $misses, $correctCount), false, $metadata];
    }
}
