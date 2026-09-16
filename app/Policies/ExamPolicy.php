<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExamPolicy
{
    public function view(User $user, Exam $exam): bool
    {
        return $this->owns($user, $exam);
    }

    public function update(User $user, Exam $exam): bool
    {
        return $this->owns($user, $exam) && $exam->isDraft();
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $this->owns($user, $exam) && $exam->isDraft();
    }

    public function generatePdf(User $user, Exam $exam): bool
    {
        return $this->owns($user, $exam) && $exam->isPublished();
    }

    public function downloadPdf(User $user, Exam $exam): bool
    {
        return $this->owns($user, $exam) && $exam->isPdfReady();
    }

    public function submit(User $user, Exam $exam): Response
    {
        if (! $this->owns($user, $exam)) {
            return Response::deny(__('Ação não autorizada.'));
        }

        if (! $exam->isPublished()) {
            return Response::deny(__('Somente provas publicadas aceitam envios.'));
        }

        if (! $exam->isPdfReady()) {
            return Response::deny(__('Gere o PDF da prova antes de aceitar envios.'));
        }

        return Response::allow();
    }

    private function owns(User $user, Exam $exam): bool
    {
        return $exam->teacher_id === $user->id;
    }
}
