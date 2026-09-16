<?php

namespace App\QuestionTypes;

use App\Enums\QuestionType;

class SingleChoiceType implements QuestionTypeInterface
{
    public function identifier(): QuestionType
    {
        return QuestionType::SingleChoice;
    }

    public function label(): string
    {
        return 'Escolha Única';
    }

    public function icon(): string
    {
        return 'circle-check';
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultMetadata(): array
    {
        return [
            'options' => [
                ['text' => '', 'correct' => false],
                ['text' => '', 'correct' => false],
            ],
        ];
    }

    /**
     * @return array{identifier: string, label: string, icon: string, default_metadata: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier()->value,
            'label' => $this->label(),
            'icon' => $this->icon(),
            'default_metadata' => $this->defaultMetadata(),
        ];
    }
}
