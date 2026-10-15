<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function view(User $user, Submission $submission): bool
    {
        return $this->owns($user, $submission);
    }

    public function update(User $user, Submission $submission): bool
    {
        return $this->owns($user, $submission);
    }

    private function owns(User $user, Submission $submission): bool
    {
        return $submission->exam->teacher_id === $user->id;
    }
}
