<?php

namespace App\Repositories;

use App\Models\SubmissionAnswer;

class SubmissionAnswerRepository
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(int $submissionId, int $questionId, array $data): SubmissionAnswer
    {
        return SubmissionAnswer::updateOrCreate(
            ['submission_id' => $submissionId, 'question_id' => $questionId],
            $data,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateGrade(SubmissionAnswer $answer, array $data): SubmissionAnswer
    {
        $answer->update($data);

        return $answer;
    }
}
