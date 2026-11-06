<?php

use App\Enums\ExamStatus;
use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;

beforeEach(function () {
    $this->teacher = User::factory()->create();
    $this->actingAs($this->teacher);
    $this->exam = Exam::factory()->create(['teacher_id' => $this->teacher->id]);
});

test('a teacher can add a question with type-specific metadata', function (QuestionType $type, array $metadata) {
    $this->post(route('exams.questions.store', $this->exam), [
        'type' => $type->value,
        'statement' => 'What is 2 + 2?',
        'weight' => 5,
        'metadata' => $metadata,
    ])->assertRedirect(route('exams.edit', $this->exam));

    $question = $this->exam->questions()->first();

    expect($question->type)->toBe($type);
    expect($question->metadata)->toEqual($metadata);
})->with([
    'essay' => [QuestionType::Essay, ['lines' => 10, 'expected_answer' => 'four']],
    'single choice' => [
        QuestionType::SingleChoice,
        [
            'options' => [
                ['text' => 'Option A', 'correct' => true],
                ['text' => 'Option B', 'correct' => false],
            ],
        ],
    ],
    'multiple choice' => [
        QuestionType::MultipleChoice,
        [
            'options' => [
                ['text' => 'Option A', 'correct' => true],
                ['text' => 'Option B', 'correct' => true],
                ['text' => 'Option C', 'correct' => false],
            ],
        ],
    ],
]);

test('grading notes are ignored for objective questions', function () {
    $this->post(route('exams.questions.store', $this->exam), [
        'type' => QuestionType::SingleChoice->value,
        'statement' => 'statement',
        'weight' => 1,
        'grading_notes' => 'deve ser ignorado',
        'metadata' => ['options' => [['text' => 'A', 'correct' => true]]],
    ])->assertRedirect(route('exams.edit', $this->exam));

    expect($this->exam->questions()->first()->grading_notes)->toBeNull();
});

test('grading notes are stored for essay questions', function () {
    $this->post(route('exams.questions.store', $this->exam), [
        'type' => QuestionType::Essay->value,
        'statement' => 'statement',
        'weight' => 1,
        'grading_notes' => 'critério de correção',
        'metadata' => ['lines' => 10],
    ])->assertRedirect(route('exams.edit', $this->exam));

    expect($this->exam->questions()->first()->grading_notes)->toBe('critério de correção');
});

test('updating an objective question clears grading notes', function () {
    $question = Question::factory()->singleChoice()->create([
        'exam_id' => $this->exam->id,
        'grading_notes' => 'nota antiga',
    ]);

    $this->put(route('exams.questions.update', [$this->exam, $question]), [
        'statement' => 'updated',
        'weight' => 5,
        'grading_notes' => 'deve ser ignorado',
    ])->assertRedirect(route('exams.edit', $this->exam));

    expect($question->fresh()->grading_notes)->toBeNull();
});

test('adding a question with an invalid type is rejected', function () {
    $this->post(route('exams.questions.store', $this->exam), [
        'type' => 'not_a_type',
        'statement' => 'statement',
        'weight' => 1,
    ])->assertSessionHasErrors('type');

    expect($this->exam->questions()->count())->toBe(0);
});

test('a question cannot be added to a non-draft exam', function () {
    $this->exam->update(['status' => ExamStatus::Published]);

    $this->post(route('exams.questions.store', $this->exam), [
        'type' => QuestionType::Essay->value,
        'statement' => 'statement',
        'weight' => 1,
    ])->assertForbidden();
});

test('questions are assigned sequential order', function () {
    $this->post(route('exams.questions.store', $this->exam), [
        'type' => QuestionType::Essay->value,
        'statement' => 'one',
        'weight' => 1,
    ]);
    $this->post(route('exams.questions.store', $this->exam), [
        'type' => QuestionType::Essay->value,
        'statement' => 'two',
        'weight' => 1,
    ]);

    expect($this->exam->questions()->pluck('order')->all())->toBe([0, 1]);
});

test('a teacher can update a question', function () {
    $question = Question::factory()->create(['exam_id' => $this->exam->id]);

    $this->put(route('exams.questions.update', [$this->exam, $question]), [
        'statement' => 'updated',
        'weight' => 9,
        'metadata' => ['lines' => 5, 'expected_answer' => 'x'],
    ])->assertRedirect(route('exams.edit', $this->exam));

    expect($question->fresh()->statement)->toBe('updated');
    expect($question->fresh()->weight)->toBe(9.0);
});

test('deleting a question renumbers the remaining questions', function () {
    $first = Question::factory()->create(['exam_id' => $this->exam->id, 'order' => 0]);
    $second = Question::factory()->create(['exam_id' => $this->exam->id, 'order' => 1]);
    $third = Question::factory()->create(['exam_id' => $this->exam->id, 'order' => 2]);

    $this->delete(route('exams.questions.destroy', [$this->exam, $second]));

    expect($first->fresh()->order)->toBe(0);
    expect($third->fresh()->order)->toBe(1);
    expect($this->exam->questions()->count())->toBe(2);
});

test('reordering updates the order sequentially', function () {
    $first = Question::factory()->create(['exam_id' => $this->exam->id, 'order' => 0]);
    $second = Question::factory()->create(['exam_id' => $this->exam->id, 'order' => 1]);
    $third = Question::factory()->create(['exam_id' => $this->exam->id, 'order' => 2]);

    $this->post(route('exams.questions.reorder', $this->exam), [
        'ordered_ids' => [$third->id, $first->id, $second->id],
    ]);

    expect($third->fresh()->order)->toBe(0);
    expect($first->fresh()->order)->toBe(1);
    expect($second->fresh()->order)->toBe(2);
});

test('a question from another exam cannot be updated', function () {
    $otherExam = Exam::factory()->create(['teacher_id' => $this->teacher->id]);
    $question = Question::factory()->create(['exam_id' => $otherExam->id]);

    $this->put(route('exams.questions.update', [$this->exam, $question]), [
        'statement' => 'hacked',
        'weight' => 1,
    ])->assertNotFound();
});
