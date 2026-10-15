<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Exam;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'student_name' => fake()->name(),
            'status' => SubmissionStatus::Uploaded,
            'submitted_at' => now(),
        ];
    }

    public function computerVisionCompleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubmissionStatus::ComputerVisionCompleted,
        ]);
    }
}
