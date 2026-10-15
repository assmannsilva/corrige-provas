<?php

namespace App\Repositories;

use App\Models\Submission;

class SubmissionRepository
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Submission
    {
        return Submission::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Submission $submission, array $data): Submission
    {
        $submission->update($data);

        return $submission;
    }
}
