<?php

namespace App\QuestionTypes;

use App\Enums\QuestionType;

class MultipleChoiceType implements QuestionTypeInterface
{
    public function identifier(): QuestionType
    {
        return QuestionType::MultipleChoice;
    }

    public function label(): string
    {
        return 'Múltipla Escolha';
    }

    public function icon(): string
    {
        return 'check-square';
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
