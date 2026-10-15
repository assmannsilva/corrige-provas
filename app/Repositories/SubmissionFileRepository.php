<?php

namespace App\Repositories;

use App\Models\SubmissionFile;

class SubmissionFileRepository
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SubmissionFile
    {
        return SubmissionFile::create($data);
    }
}
