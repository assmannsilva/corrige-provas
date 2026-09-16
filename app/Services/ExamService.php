<?php

namespace App\Services;

use App\Enums\ExamStatus;
use App\Exceptions\InvalidExamStatusException;
use App\Models\Exam;
use App\Repositories\ExamRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExamService
{
    public function __construct(
        private readonly ExamRepository $examRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $teacherId, array $data): Exam
    {
        return $this->examRepository->create([
            ...$data,
            'teacher_id' => $teacherId,
            'status' => ExamStatus::Draft,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Exam $exam, array $data): Exam
    {
        if (! $exam->isDraft()) {
            throw new InvalidExamStatusException;
        }

        return $this->examRepository->update($exam, $data);
    }

    public function delete(Exam $exam): void
    {
        if (! $exam->isDraft()) {
            throw new InvalidExamStatusException;
        }

        $this->examRepository->delete($exam);
    }

    public function publish(Exam $exam): Exam
    {
        if (! $exam->isDraft()) {
            throw new InvalidExamStatusException;
        }

        return $this->examRepository->update($exam, [
            'status' => ExamStatus::Published,
        ]);
    }

    public function archive(Exam $exam): Exam
    {
        if (! $exam->isPublished()) {
            throw new InvalidExamStatusException;
        }

        return $this->examRepository->update($exam, [
            'status' => ExamStatus::Archived,
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, Exam>
     */
    public function findByTeacherPaginated(
        int $teacherId,
        int $perPage = 10,
        ?string $search = null,
        ?string $status = null
    ): LengthAwarePaginator {
        return $this->examRepository->findByTeacherPaginated(
            $teacherId,
            $perPage,
            $search,
            $status
        );
    }
}
