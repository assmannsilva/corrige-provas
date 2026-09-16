<?php

namespace App\Services;

use App\Exceptions\InvalidExamStatusException;
use App\Models\Exam;
use App\Models\Question;
use App\Repositories\QuestionRepository;
use Illuminate\Support\Facades\DB;

class QuestionService
{
    public function __construct(
        private readonly QuestionRepository $questionRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function addQuestion(Exam $exam, array $data): Question
    {
        if (! $exam->isDraft()) {
            throw new InvalidExamStatusException;
        }

        $maxOrder = $exam->questions()->max('order') ?? -1;

        return $this->questionRepository->create([
            ...$data,
            'exam_id' => $exam->id,
            'order' => $maxOrder + 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateQuestion(Question $question, array $data): Question
    {
        if (! $question->exam->isDraft()) {
            throw new InvalidExamStatusException;
        }

        return $this->questionRepository->update($question, $data);
    }

    public function deleteQuestion(Question $question): void
    {
        if (! $question->exam->isDraft()) {
            throw new InvalidExamStatusException;
        }

        DB::transaction(function () use ($question) {
            $this->questionRepository->delete($question);
            $exam = $question->exam;

            /** @var list<int> $orderedIds */
            $orderedIds = $exam->questions()
                ->orderBy('order')
                ->pluck('id')
                ->all();

            $this->questionRepository->updateQuestionsOrder($exam, $orderedIds);
        });
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorderQuestions(Exam $exam, array $orderedIds): void
    {
        if (! $exam->isDraft()) {
            throw new InvalidExamStatusException;
        }

        DB::transaction(fn () => $this->questionRepository->updateQuestionsOrder($exam, $orderedIds));
    }
}
