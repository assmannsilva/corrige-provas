<?php

namespace App\Jobs;

use App\Models\Submission;
use App\Services\GradingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessSubmissionGradingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public function __construct(private readonly Submission $submission) {}

    /**
     * Grade extracted answers asynchronously, separately from image processing.
     */
    public function handle(GradingService $gradingService): void
    {
        $gradingService->run($this->submission);
    }
}
