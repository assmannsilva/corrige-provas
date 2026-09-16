<?php

namespace App\QuestionTypes;

use App\Enums\QuestionType;

class EssayType implements QuestionTypeInterface
{
    public function identifier(): QuestionType
    {
        return QuestionType::Essay;
    }

    public function label(): string
    {
        return 'Dissertação';
    }

    public function icon(): string
    {
        return 'align-left';
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultMetadata(): array
    {
        return [
            'lines' => 10,
            'expected_answer' => '',
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
