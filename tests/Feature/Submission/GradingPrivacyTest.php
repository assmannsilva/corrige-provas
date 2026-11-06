<?php

use App\Models\Exam;
use App\Models\Question;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Support\Grading\Graders\EssayGrader;
use Illuminate\Support\Facades\Http;

test('essay grading excludes local student identification', function () {
    config(['services.grading.api_key' => 'synthetic-test-key']);
    Http::preventStrayRequests();
    Http::fake([
        'https://openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'score' => 1, 'feedback' => 'Correto.', 'needs_review' => false,
            ])]]],
        ]),
    ]);
    $exam = Exam::factory()->create();
    $question = Question::factory()->essay()->create([
        'exam_id' => $exam->id, 'weight' => 2, 'statement' => 'Explique a resposta.',
    ]);
    $submission = Submission::factory()->create([
        'exam_id' => $exam->id, 'student_name' => 'Name Sentinel',
        'student_class' => 'Class Sentinel',
    ]);
    $answer = SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id, 'question_id' => $question->id,
        'raw_text' => 'Resposta sintética.',
    ]);

    app(EssayGrader::class)->grade($question, $answer);

    Http::assertSent(function ($request) {
        $body = json_encode($request->data());

        return $request['model'] === config('services.grading.model')
            && str_contains($body, 'Resposta')
            && ! str_contains($body, 'Sentinel')
            && ! str_contains($body, 'image_url')
            && ! str_contains($body, 'student_name');
    });
    Http::assertSentCount(1);
});

test('grading uses the configured model override', function () {
    config(['services.grading.model' => 'custom/model']);
    Http::fake(['*' => Http::response([
        'choices' => [['message' => ['content' => '{"score":1,"feedback":"OK","needs_review":false}']]],
    ])]);

    app(\App\Support\Grading\GradingClient::class)->grade('Critérios', 'Resposta');

    Http::assertSent(fn ($request) => $request['model'] === 'custom/model');
});
