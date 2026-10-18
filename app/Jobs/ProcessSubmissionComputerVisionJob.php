<?php

namespace App\Jobs;

use App\Actions\Submission\ComputerVision\ProcessComputerVisionExtraction;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessSubmissionComputerVisionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public function __construct(private readonly Submission $submission) {}

    /**
     * Extract answers from the ordered submission files without resolving student identity.
     */
    public function handle(ProcessComputerVisionExtraction $action): void
    {
        $action->handle($this->submission);
    }
}
