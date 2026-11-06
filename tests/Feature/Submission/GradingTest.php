<?php

use App\Actions\Submission\ComputerVision\ProcessComputerVisionExtraction;
use App\Enums\ExamStatus;
use App\Enums\SubmissionAnswerStatus;
use App\Enums\SubmissionStatus;
use App\Jobs\ProcessSubmissionGradingJob;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Models\User;
use App\Services\GradingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->teacher = User::factory()->create();
    $this->actingAs($this->teacher);
    $this->exam = Exam::factory()->create([
        'teacher_id' => $this->teacher->id,
        'status' => ExamStatus::Published,
    ]);
});

test('a correct single choice answer receives full weight', function () {
    $question = Question::factory()->singleChoice()->create([
        'exam_id' => $this->exam->id,
        'weight' => 5,
        'order' => 0,
    ]);

    $submission = Submission::factory()->computerVisionCompleted()->create(['exam_id' => $this->exam->id]);

    SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $question->id,
        'raw_text' => 'A',
    ]);

    app(GradingService::class)->run($submission);

    $submission->refresh();

    expect($submission->status)->toBe(SubmissionStatus::Completed);
    expect($submission->total_score)->toBe(5.0);
    expect($submission->graded_at)->not->toBeNull();

    $answer = $submission->answers()->first();

    expect($answer->score)->toBe(5.0);
    expect($answer->needs_review)->toBeFalse();
});

test('an incorrect single choice answer receives zero', function () {
    $question = Question::factory()->singleChoice()->create([
        'exam_id' => $this->exam->id,
        'weight' => 5,
        'order' => 0,
    ]);

    $submission = Submission::factory()->computerVisionCompleted()->create(['exam_id' => $this->exam->id]);

    SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $question->id,
        'raw_text' => 'B',
    ]);

    app(GradingService::class)->run($submission);

    $answer = $submission->answers()->first();

    expect($answer->score)->toBe(0.0);
    expect($answer->needs_review)->toBeFalse();
});

test('an unchecked choice answer is scored as blank', function () {
    $question = Question::factory()->singleChoice()->create([
        'exam_id' => $this->exam->id,
        'weight' => 5,
        'order' => 0,
    ]);

    $submission = Submission::factory()->computerVisionCompleted()->create(['exam_id' => $this->exam->id]);

    SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $question->id,
        'raw_text' => 'UNCHECKED',
    ]);

    app(GradingService::class)->run($submission);

    $answer = $submission->answers()->first();

    expect($answer->score)->toBe(0.0);
    expect($answer->needs_review)->toBeFalse();
});

test('a multiple choice answer receives partial credit', function () {
    $question = Question::factory()->multipleChoice()->create([
        'exam_id' => $this->exam->id,
        'weight' => 4,
        'order' => 0,
    ]);

    // options: A(correct), B(correct), C(incorrect) => 2 correct, 1 wrong
    $score = function (string $raw) use ($question) {
        $submission = Submission::factory()->computerVisionCompleted()->create(['exam_id' => $this->exam->id]);

        SubmissionAnswer::factory()->create([
            'submission_id' => $submission->id,
            'question_id' => $question->id,
            'raw_text' => $raw,
        ]);

        app(GradingService::class)->run($submission);

        return $submission->answers()->first()->score;
    };

    expect($score('A,B'))->toBe(4.0);   // 2 correct, 0 wrong → (1 - 0) × 4
    expect($score('A'))->toBe(2.0);     // 1 correct, 0 wrong → (0.5 - 0) × 4
    expect($score('A,C'))->toBe(0.0);   // 1 correct, 1 wrong → (0.5 - 1) × 4 → clamped to 0
    expect($score('UNCHECKED'))->toBe(0.0);
});

test('multiple choice accounts for both correct and wrong fractions', function () {
    $question = Question::factory()->multipleChoice()->create([
        'exam_id' => $this->exam->id,
        'weight' => 1,
        'order' => 0,
        'metadata' => [
            'options' => [
                ['text' => 'A', 'correct' => true],
                ['text' => 'B', 'correct' => true],
                ['text' => 'C', 'correct' => true],
                ['text' => 'D', 'correct' => false],
                ['text' => 'E', 'correct' => false],
            ],
        ],
    ]);

    $score = function (string $raw) use ($question) {
        $submission = Submission::factory()->computerVisionCompleted()->create(['exam_id' => $this->exam->id]);

        SubmissionAnswer::factory()->create([
            'submission_id' => $submission->id,
            'question_id' => $question->id,
            'raw_text' => $raw,
        ]);

        app(GradingService::class)->run($submission);

        return $submission->answers()->first()->score;
    };

    // 3 correct, 2 wrong
    expect($score('A,B,C'))->toBe(1.0); // (3/3 - 0/2)
    expect($score('A,B,C,D'))->toBe(0.5); // (3/3 - 1/2) = 0.5
    expect($score('A,B,C,D,E'))->toBe(0.0); // (3/3 - 2/2) = 0
});

test('a blank essay answer receives zero', function () {
    $question = Question::factory()->essay()->create([
        'exam_id' => $this->exam->id,
        'weight' => 10,
        'order' => 0,
    ]);

    $submission = Submission::factory()->computerVisionCompleted()->create(['exam_id' => $this->exam->id]);

    SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $question->id,
        'raw_text' => '',
    ]);

    app(GradingService::class)->run($submission);

    $answer = $submission->answers()->first();

    expect($answer->score)->toBe(0.0);
    expect($answer->needs_review)->toBeFalse();
});

test('an essay answer is graded by the llm', function () {
    $question = Question::factory()->essay()->create([
        'exam_id' => $this->exam->id,
        'weight' => 10,
        'order' => 0,
    ]);

    $submission = Submission::factory()->computerVisionCompleted()->create(['exam_id' => $this->exam->id]);

    SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $question->id,
        'raw_text' => 'resposta do aluno',
    ]);

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['content' => '{"score": 8, "feedback": "Correto, mas incompleto.", "needs_review": false}']],
            ],
        ]),
    ]);

    app(GradingService::class)->run($submission);

    $submission->refresh();

    expect($submission->status)->toBe(SubmissionStatus::Completed);
    expect($submission->total_score)->toBe(8.0);

    $answer = $submission->answers()->first();

    expect($answer->score)->toBe(8.0);
    expect($answer->feedback)->toBe('Correto, mas incompleto.');
    expect($answer->needs_review)->toBeFalse();
    expect($answer->grade_metadata['method'])->toBe('llm');
});

test('a decimal essay score is accepted', function () {
    $question = Question::factory()->essay()->create([
        'exam_id' => $this->exam->id,
        'weight' => 10,
        'order' => 0,
    ]);

    $submission = Submission::factory()->computerVisionCompleted()->create(['exam_id' => $this->exam->id]);

    SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $question->id,
        'raw_text' => 'resposta do aluno',
    ]);

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['content' => '{"score": 8.5, "feedback": "Correto, mas incompleto.", "needs_review": false}']],
            ],
        ]),
    ]);

    app(GradingService::class)->run($submission);

    $answer = $submission->answers()->first();

    expect($answer->score)->toBe(8.5);
    expect($answer->needs_review)->toBeFalse();
});

test('a decimal essay score above the maximum is flagged for review', function () {
    $question = Question::factory()->essay()->create([
        'exam_id' => $this->exam->id,
        'weight' => 10,
        'order' => 0,
    ]);

    $submission = Submission::factory()->computerVisionCompleted()->create(['exam_id' => $this->exam->id]);

    SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $question->id,
        'raw_text' => 'resposta do aluno',
    ]);

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['content' => '{"score": 10.1, "feedback": "x", "needs_review": false}']],
            ],
        ]),
    ]);

    app(GradingService::class)->run($submission);

    $answer = $submission->answers()->first();

    expect($answer->score)->toBeNull();
    expect($answer->needs_review)->toBeTrue();
});

test('an out-of-range llm score is flagged for review', function () {
    $question = Question::factory()->essay()->create([
        'exam_id' => $this->exam->id,
        'weight' => 10,
        'order' => 0,
    ]);

    $submission = Submission::factory()->computerVisionCompleted()->create(['exam_id' => $this->exam->id]);

    SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $question->id,
        'raw_text' => 'resposta do aluno',
    ]);

    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['content' => '{"score": 99, "feedback": "x", "needs_review": false}']],
            ],
        ]),
    ]);

    app(GradingService::class)->run($submission);

    $answer = $submission->answers()->first();

    expect($answer->score)->toBeNull();
    expect($answer->needs_review)->toBeTrue();
});

test('a failed extraction is flagged for review and left unscored', function () {
    $question = Question::factory()->essay()->create([
        'exam_id' => $this->exam->id,
        'weight' => 10,
        'order' => 0,
    ]);

    $submission = Submission::factory()->computerVisionCompleted()->create(['exam_id' => $this->exam->id]);

    SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $question->id,
        'raw_text' => null,
        'status' => SubmissionAnswerStatus::Failed,
    ]);

    app(GradingService::class)->run($submission);

    $answer = $submission->answers()->first();

    expect($answer->score)->toBeNull();
    expect($answer->needs_review)->toBeTrue();
});

test('a grading pass preserves a manual review flag set during extraction', function () {
    $question = Question::factory()->singleChoice()->create([
        'exam_id' => $this->exam->id,
        'weight' => 5,
        'order' => 0,
    ]);

    $submission = Submission::factory()->computerVisionCompleted()->create(['exam_id' => $this->exam->id]);

    SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $question->id,
        'raw_text' => 'A',
        'needs_review' => true,
    ]);

    app(GradingService::class)->run($submission);

    $answer = $submission->answers()->first();

    expect($answer->score)->toBe(5.0);
    expect($answer->needs_review)->toBeTrue();
});

test('a teacher can override an answer score', function () {
    $question = Question::factory()->singleChoice()->create([
        'exam_id' => $this->exam->id,
        'weight' => 5,
        'order' => 0,
    ]);

    $submission = Submission::factory()->create([
        'exam_id' => $this->exam->id,
        'status' => SubmissionStatus::Completed,
        'total_score' => 5,
    ]);

    $answer = SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $question->id,
        'raw_text' => 'A',
        'score' => 5,
        'needs_review' => false,
    ]);

    $this->patch(route('submissions.answers.update', [$submission, $answer]), [
        'score' => 3,
        'feedback' => 'Nota ajustada pelo professor.',
    ])->assertRedirect(route('submissions.show', $submission));

    $answer->refresh();
    $submission->refresh();

    expect($answer->score)->toBe(3.0);
    expect($answer->needs_review)->toBeFalse();
    expect($submission->total_score)->toBe(3.0);
});

test('an override cannot exceed the question weight', function () {
    $question = Question::factory()->singleChoice()->create([
        'exam_id' => $this->exam->id,
        'weight' => 5,
        'order' => 0,
    ]);

    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);

    $answer = SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $question->id,
        'raw_text' => 'A',
    ]);

    $this->patch(route('submissions.answers.update', [$submission, $answer]), [
        'score' => 6,
    ])->assertSessionHasErrors('score');
});

test('another teacher cannot override an answer score', function () {
    $question = Question::factory()->singleChoice()->create([
        'exam_id' => $this->exam->id,
        'weight' => 5,
        'order' => 0,
    ]);

    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);

    $answer = SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $question->id,
        'raw_text' => 'A',
    ]);

    $other = User::factory()->create();
    $this->actingAs($other);

    $this->patch(route('submissions.answers.update', [$submission, $answer]), [
        'score' => 3,
    ])->assertForbidden();
});

test('the computer vision pipeline dispatches the grading job after success', function () {
    Queue::fake();

    $question = Question::factory()->singleChoice()->create([
        'exam_id' => $this->exam->id,
        'order' => 0,
        'computer_vision_region' => ['regions' => [
            ['page' => 1, 'x' => 0.08, 'y' => 0.3, 'width' => 0.02, 'height' => 0.01, 'key' => 'choice:0', 'extraction_type' => 'choice'],
        ]],
    ]);

    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);

    Http::fake([
        'http://python:8000/process' => Http::response([
            'results' => [
                ['question_id' => $question->id, 'raw_text' => 'A', 'confidence' => null, 'status' => 'extracted'],
            ],
        ]),
    ]);

    app(ProcessComputerVisionExtraction::class)->handle($submission);

    Queue::assertPushed(ProcessSubmissionGradingJob::class);
});
