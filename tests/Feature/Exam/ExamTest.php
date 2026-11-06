<?php

use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;

beforeEach(function () {
    $this->teacher = User::factory()->create();
    $this->actingAs($this->teacher);
    $this->exam = Exam::factory()->create(['teacher_id' => $this->teacher->id]);
});

test('deleting a draft exam removes its questions', function () {
    $question = Question::factory()->create(['exam_id' => $this->exam->id]);

    $this->delete(route('exams.destroy', $this->exam))
        ->assertRedirect(route('exams.index'));

    expect(Exam::find($this->exam->id))->toBeNull();
    expect(Question::find($question->id))->toBeNull();
});

test('a non-draft exam cannot be deleted', function (ExamStatus $status) {
    $this->exam->update(['status' => $status]);

    $this->delete(route('exams.destroy', $this->exam))->assertForbidden();

    expect(Exam::find($this->exam->id))->not->toBeNull();
})->with([
    ExamStatus::Published,
    ExamStatus::Archived,
]);

test('an exam cannot be deleted by another teacher', function () {
    $other = User::factory()->create();
    $this->actingAs($other);

    $this->delete(route('exams.destroy', $this->exam))->assertForbidden();

    expect(Exam::find($this->exam->id))->not->toBeNull();
});

test('an exam serializes its total_points and pdf_ready', function () {
    Question::factory()->create(['exam_id' => $this->exam->id, 'weight' => 7]);
    Question::factory()->create(['exam_id' => $this->exam->id, 'weight' => 3]);

    $array = $this->exam->fresh()->toArray();

    expect($array['total_points'])->toBe(10);
    expect($array['pdf_ready'])->toBeFalse();
});
