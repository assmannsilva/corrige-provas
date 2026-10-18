<?php

namespace App\Actions\Submission\ComputerVision;

use App\Enums\ComputerVisionProcessingStatus;
use App\Enums\SubmissionStatus;
use App\Jobs\ProcessSubmissionGradingJob;
use App\Models\ComputerVisionProcessing;
use App\Models\Submission;
use App\Repositories\ComputerVisionProcessingRepository;
use App\Repositories\SubmissionRepository;
use App\Support\ComputerVision\ComputerVisionClient;
use Throwable;

final class ProcessComputerVisionExtraction
{
    public function __construct(
        private readonly ComputerVisionProcessingRepository $computerVisionProcessingRepository,
        private readonly SubmissionRepository $submissionRepository,
        private readonly BuildComputerVisionPayload $buildPayload,
        private readonly ComputerVisionClient $computerVisionClient,
        private readonly PersistComputerVisionResults $persistResults,

    ) {}

    private function updateProcessingStatus(Submission $submission): ComputerVisionProcessing
    {

        $processingLog = $this->computerVisionProcessingRepository->create([
            'submission_id' => $submission->id,
            'status' => ComputerVisionProcessingStatus::Processing,
            'started_at' => now(),
        ]);

        $this->submissionRepository->update($submission, [
            'status' => SubmissionStatus::Processing,
        ]);

        return $processingLog;
    }

    private function updateFailedStatus(
        Submission $submission,
        ?ComputerVisionProcessing $processingLog,
        string $failedMessage
    ): void {
        if ($processingLog !== null) {
            $this->computerVisionProcessingRepository->update($processingLog, [
                'status' => ComputerVisionProcessingStatus::Failed,
                'error_message' => $failedMessage,
                'completed_at' => now(),
            ]);
        }

        $this->submissionRepository->update($submission, [
            'status' => SubmissionStatus::Failed,
        ]);
    }

    public function handle(Submission $submission): void
    {
        $processingLog = null;

        try {
            $processingLog = $this->updateProcessingStatus($submission);

            $this->buildPayload->execute($submission)
                |> $this->computerVisionClient->process(...)
                |> (fn ($result) => $this->persistResults->execute(
                    $result,
                    $submission,
                    $processingLog,
                ));

            ProcessSubmissionGradingJob::dispatch($submission);
        } catch (Throwable $th) {
            $this->updateFailedStatus($submission, $processingLog, $th->getMessage());
            throw $th;
        }
    }
}
