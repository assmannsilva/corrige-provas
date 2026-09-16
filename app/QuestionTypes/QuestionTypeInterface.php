<?php

namespace App\QuestionTypes;

use App\Enums\QuestionType;

interface QuestionTypeInterface
{
    public function identifier(): QuestionType;

    public function label(): string;

    public function icon(): string;

    /**
     * @return array<string, mixed>
     */
    public function defaultMetadata(): array;

    /**
     * @return array{identifier: string, label: string, icon: string, default_metadata: array<string, mixed>}
     */
    public function toArray(): array;
}
