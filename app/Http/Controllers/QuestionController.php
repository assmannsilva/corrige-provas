<?php

namespace App\Http\Controllers;

use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\Question;
use App\Services\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class QuestionController extends Controller
{
    public function __construct(
        private readonly QuestionService $questionService,
    ) {
    }

    private function authorizeExamQuestion(Exam $exam, ?Question $question = null): void
    {
        $this->authorize('update', $exam);
        abort_if($question && $question->exam_id !== $exam->id, 404);
    }

    public function store(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExamQuestion($exam);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(QuestionType::class)],
            'statement' => ['required', 'string'],
            'weight' => ['required', 'numeric', 'min:0.1'],
            'grading_notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        if ($validated['type'] !== QuestionType::Essay->value) {
            $validated['grading_notes'] = null;
        }

        $this->questionService->addQuestion($exam, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Questão adicionada.')]);

        return to_route('exams.edit', $exam);
    }

    public function update(Request $request, Exam $exam, Question $question): RedirectResponse
    {
        $this->authorizeExamQuestion($exam, $question);

        $validated = $request->validate([
            'statement' => ['required', 'string'],
            'weight' => ['required', 'numeric', 'min:0.1'],
            'grading_notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        if ($question->type !== QuestionType::Essay) {
            $validated['grading_notes'] = null;
        }

        $this->questionService->updateQuestion($question, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Questão atualizada.')]);

        return to_route('exams.edit', $exam);
    }

    public function destroy(Exam $exam, Question $question): RedirectResponse
    {
        $this->authorizeExamQuestion($exam, $question);

        $this->questionService->deleteQuestion($question);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Questão removida.')]);

        return to_route('exams.edit', $exam);
    }

    public function reorder(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExamQuestion($exam);

        $validated = $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['required', 'integer', 'exists:questions,id'],
        ]);

        $this->questionService->reorderQuestions($exam, $validated['ordered_ids']);

        return to_route('exams.edit', $exam);
    }
}
