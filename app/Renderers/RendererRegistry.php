<?php

namespace App\Renderers;

use App\Enums\QuestionType;
use RuntimeException;

class RendererRegistry
{
    /**
     * @var array<string, QuestionRendererInterface>
     */
    private array $renderers = [];

    public function register(QuestionType $type, QuestionRendererInterface $renderer): void
    {
        $this->renderers[$type->value] = $renderer;
    }

    public function get(QuestionType $type): QuestionRendererInterface
    {
        return $this->renderers[$type->value]
            ?? throw new RuntimeException("No renderer registered for type [{$type->value}].");
    }
}
