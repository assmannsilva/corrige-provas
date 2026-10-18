<?php

namespace App\Actions\Submission\ComputerVision;

use App\Models\Question;
use App\Models\Submission;

final class BuildComputerVisionPayload
{
    /**
     * Build a request for the local vision service in upload-page order.
     *
     * The teacher-entered name and class stay in Laravel and are not part of this request.
     *
     * @return array<string, mixed>
     */
    public function execute(Submission $submission): array
    {
        $submission->loadMissing('exam.questions', 'files');

        $exam = $submission->exam;

        $files = $submission->files
            ->sortBy('order')
            ->pluck('path')
            ->all();

        $questions = $exam->questions
            ->map(fn (Question $question) => [
                'id' => $question->id,
                'type' => $question->type->value,
                'regions' => $question->computer_vision_region['regions'] ?? [],
            ])
            ->values()
            ->all();

        return [
            'submission_id' => $submission->id,
            'files' => $files,
            'template' => $exam->pdf_path,
            'questions' => $questions,
        ];
    }
}
