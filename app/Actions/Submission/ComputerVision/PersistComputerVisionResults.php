<?php

namespace App\Actions\Submission\ComputerVision;

use App\Enums\ComputerVisionProcessingStatus;
use App\Enums\SubmissionAnswerStatus;
use App\Enums\SubmissionStatus;
use App\Models\ComputerVisionProcessing;
use App\Models\Submission;
use App\Repositories\ComputerVisionProcessingRepository;
use App\Repositories\SubmissionAnswerRepository;
use App\Repositories\SubmissionRepository;
use Illuminate\Support\Facades\DB;

class PersistComputerVisionResults
{
    public function __construct(
        private readonly ComputerVisionProcessingRepository $computerVisionProcessingRepository,
        private readonly SubmissionAnswerRepository $submissionAnswerRepository,
        private readonly SubmissionRepository $submissionRepository,
    ) {}

    /**
     * Persiste somente respostas; a identificação manual nunca vem do processador.
     * Reprocessamentos preservam o nome salvo pelo professor.
     *
     * @param  array<string, mixed>  $result
     */
    public function execute(
        array $result,
        Submission $submission,
        ComputerVisionProcessing $processing
    ): void {

        DB::transaction(function () use ($submission, $processing, $result) {
            foreach ($result['results'] ?? [] as $item) {
                $questionId = (int) $item['question_id'];

                $this->submissionAnswerRepository->upsert(
                    $submission->id,
                    $questionId,
                    [
                        'raw_text' => $item['raw_text'] ?? null,
                        'crop_path' => $item['crop'] ?? null,
                        'status' => SubmissionAnswerStatus::tryFrom((string) ($item['status'] ?? '')) ?? SubmissionAnswerStatus::Failed,
                        'metadata' => $item['metadata'] ?? null,
                        'needs_review' => (bool) ($item['uncertain'] ?? false),
                    ],
                );
            }

            $this->submissionRepository->update($submission, [
                'status' => SubmissionStatus::ComputerVisionCompleted,
            ]);

            $this->computerVisionProcessingRepository->update($processing, [
                'status' => ComputerVisionProcessingStatus::Completed,
                'completed_at' => now(),
            ]);
        });

    }
}
