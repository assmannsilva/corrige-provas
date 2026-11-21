<?php

namespace App\Services;

use App\Enums\SubmissionFileType;
use App\Enums\SubmissionStatus;
use App\Jobs\ProcessSubmissionComputerVisionJob;
use App\Models\Exam;
use App\Models\Submission;
use App\Repositories\SubmissionFileRepository;
use App\Repositories\SubmissionRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SubmissionService
{
    public function __construct(
        private readonly SubmissionRepository $submissionRepository,
        private readonly SubmissionFileRepository $submissionFileRepository,
    ) {}

    /**
     * Associa o nome informado manualmente antes de enfileirar a extração.
     * Remove os arquivos se a transação falhar.
     *
     * @param  list<UploadedFile>  $files
     */
    public function create(Exam $exam, array $files, string $studentName, ?string $studentClass = null): Submission
    {
        $directory = null;

        try {
            $submission = DB::transaction(function () use ($exam, $files, $studentName, $studentClass, &$directory) {
                $submission = $this->submissionRepository->create([
                    'exam_id' => $exam->id,
                    'student_name' => $studentName,
                    'student_class' => $studentClass,
                    'status' => SubmissionStatus::Uploaded,
                    'submitted_at' => now(),
                ]);

                $directory = 'submissions/'.$submission->id;

                foreach ($files as $order => $file) {
                    $path = $file->store($directory, 'local');

                    $this->submissionFileRepository->create([
                        'submission_id' => $submission->id,
                        'type' => SubmissionFileType::defineFileTypeFromUploadedFile($file),
                        'path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'order' => $order,
                    ]);
                }

                return $submission;
            });
        } catch (Throwable $e) {
            if ($directory !== null) {
                Storage::disk('local')->deleteDirectory($directory);
            }

            report($e);

            throw $e;
        }

        ProcessSubmissionComputerVisionJob::dispatch($submission);

        return $submission;
    }

    /**
     * Updates an Student from the submission
     */
    public function updateStudent(Submission $submission, string $name, ?string $class): Submission
    {
        return $this->submissionRepository->update($submission, [
            'student_name' => $name,
            'student_class' => $class,
        ]);
    }
}
