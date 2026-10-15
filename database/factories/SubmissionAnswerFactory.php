<?php

namespace Database\Factories;

use App\Enums\SubmissionAnswerStatus;
use App\Models\Question;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionAnswer>
 */
class SubmissionAnswerFactory extends Factory
{
    protected $model = SubmissionAnswer::class;

    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'question_id' => Question::factory(),
            'raw_text' => fake()->sentence(),
            'status' => SubmissionAnswerStatus::Extracted,
        ];
    }
}
