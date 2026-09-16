<?php

namespace App\QuestionTypes;

class QuestionTypeRegistry
{
    /**
     * @var array<string, QuestionTypeInterface>
     */
    private array $types = [];

    public function register(QuestionTypeInterface $type): void
    {
        $this->types[$type->identifier()->value] = $type;
    }

    /**
     * @return list<array{identifier: string, label: string, icon: string, default_metadata: array<string, mixed>}>
     */
    public function toArray(): array
    {
        return array_values(array_map(
            fn (QuestionTypeInterface $type) => $type->toArray(),
            $this->types,
        ));
    }
}
