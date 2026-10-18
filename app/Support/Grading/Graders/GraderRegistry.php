<?php

namespace App\Support\Grading\Graders;

use App\Enums\QuestionType;

class GraderRegistry
{
    /** @var array<string, Grader> */
    private array $graders = [];

    public function register(QuestionType $type, Grader $grader): void
    {
        $this->graders[$type->value] = $grader;
    }

    public function get(QuestionType $type): ?Grader
    {
        return $this->graders[$type->value] ?? null;
    }
}
