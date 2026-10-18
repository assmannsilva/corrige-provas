<?php

namespace App\Support\Grading\Graders;

use App\Models\Question;
use App\Models\SubmissionAnswer;

interface Grader
{
    /**
     * @return array{0: float|null, 1: string|null, 2: bool, 3: array<string, mixed>}
     */
    public function grade(Question $question, SubmissionAnswer $answer): array;
}
