<?php

namespace App\Repositories;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;

class QuestionRepository
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Question
    {
        return Question::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Question $question, array $data): Question
    {
        $question->update($data);

        return $question;
    }

    public function delete(Question $question): void
    {
        $question->delete();
    }

    /**
     * @return Collection<int, Question>
     */
    public function findByExam(int $examId): Collection
    {
        return Question::where('exam_id', $examId)
            ->orderBy('order')
            ->get();
    }

    public function findById(int $id): ?Question
    {
        return Question::find($id);
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function updateQuestionsOrder(Exam $exam, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Question::where('id', $id)
                ->where('exam_id', $exam->id)
                ->update(['order' => $index]);
        }
    }
}
