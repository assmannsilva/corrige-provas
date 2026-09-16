<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'type' => fake()->randomElement(QuestionType::cases()),
            'statement' => fake()->sentence(8),
            'weight' => fake()->numberBetween(1, 10),
            'order' => 0,
            'grading_notes' => fake()->optional()->sentence(),
            'metadata' => null,
        ];
    }

    public function essay(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::Essay,
            'metadata' => [
                'lines' => 10,
                'expected_answer' => fake()->sentence(),
            ],
        ]);
    }

    public function singleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::SingleChoice,
            'metadata' => [
                'options' => [
                    ['text' => 'Option A', 'correct' => true],
                    ['text' => 'Option B', 'correct' => false],
                ],
            ],
        ]);
    }

    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => QuestionType::MultipleChoice,
            'metadata' => [
                'options' => [
                    ['text' => 'Option A', 'correct' => true],
                    ['text' => 'Option B', 'correct' => true],
                    ['text' => 'Option C', 'correct' => false],
                ],
            ],
        ]);
    }
}
