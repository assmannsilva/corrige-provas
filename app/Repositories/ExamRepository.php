<?php

namespace App\Repositories;

use App\Models\Exam;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExamRepository
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Exam
    {
        return Exam::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Exam $exam, array $data): Exam
    {
        $exam->update($data);

        return $exam;
    }

    public function delete(Exam $exam): void
    {
        $exam->delete();
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
        $query = Exam::where('teacher_id', $teacherId)
            ->withCount('questions')
            ->withSum('questions', 'weight')
            ->orderByDesc('created_at');

        if ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }
}
