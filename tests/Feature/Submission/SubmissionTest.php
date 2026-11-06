<?php

use App\Actions\Submission\ComputerVision\ProcessComputerVisionExtraction;
use App\Enums\ExamStatus;
use App\Enums\SubmissionAnswerStatus;
use App\Enums\SubmissionFileType;
use App\Enums\SubmissionStatus;
use App\Jobs\ProcessSubmissionComputerVisionJob;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Models\User;
use App\Repositories\SubmissionFileRepository;
use App\Services\SubmissionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');

    $this->teacher = User::factory()->create();
    $this->actingAs($this->teacher);
    $this->exam = Exam::factory()->create([
        'teacher_id' => $this->teacher->id,
        'status' => ExamStatus::Published,
        'pdf_path' => 'exam-pdfs/1/test.pdf',
    ]);
});

test('a teacher can upload a pdf submission', function () {
    Queue::fake();

    $file = UploadedFile::fake()->create('exam.pdf', 100, 'application/pdf');

    $this->from(route('exams.show', $this->exam))
        ->post(route('exams.submissions.store', $this->exam), ['student_name' => 'Aluno Exemplo', 'files' => [$file]])
        ->assertRedirect(route('exams.show', $this->exam));

    $submission = Submission::first();

    expect($submission)->not->toBeNull();
    expect($submission->exam_id)->toBe($this->exam->id);
    expect($submission->status)->toBe(SubmissionStatus::Uploaded);
    expect($submission->files()->count())->toBe(1);
    expect(Storage::disk('local')->exists($submission->files()->first()->path))->toBeTrue();

    Queue::assertPushed(ProcessSubmissionComputerVisionJob::class);
});

test('a failed submission upload leaves no orphan files', function () {
    Queue::fake();

    $file = UploadedFile::fake()->create('exam.pdf', 100, 'application/pdf');

    $this->mock(SubmissionFileRepository::class, function ($mock) {
        $mock->shouldReceive('create')->once()->andThrow(new RuntimeException('disk full'));
    });

    expect(fn () => app(SubmissionService::class)->create($this->exam, [$file], 'Aluno Exemplo'))
        ->toThrow(RuntimeException::class);

    expect(Submission::count())->toBe(0);
    expect(Storage::disk('local')->allFiles('submissions'))->toBeEmpty();
});

test('an image submission is accepted', function () {
    Queue::fake();

    $file = UploadedFile::fake()->create('page.jpg', 100, 'image/jpeg');

    $this->from(route('exams.show', $this->exam))
        ->post(route('exams.submissions.store', $this->exam), ['student_name' => 'Aluno Exemplo', 'files' => [$file]])
        ->assertRedirect(route('exams.show', $this->exam));

    $submission = Submission::first();

    expect($submission->files()->count())->toBe(1);
    expect($submission->files()->first()->type)->toBe(SubmissionFileType::Image);
});

test('multiple files keep their order and type', function () {
    Queue::fake();

    $pdf = UploadedFile::fake()->create('page1.pdf', 100, 'application/pdf');
    $jpg = UploadedFile::fake()->create('page2.jpg', 100, 'image/jpeg');
    $png = UploadedFile::fake()->create('page3.png', 100, 'image/png');

    $this->from(route('exams.show', $this->exam))
        ->post(route('exams.submissions.store', $this->exam), ['student_name' => 'Aluno Exemplo', 'files' => [$pdf, $jpg, $png]])
        ->assertRedirect(route('exams.show', $this->exam));

    $files = Submission::first()->files()->orderBy('order')->get();

    expect($files)->toHaveCount(3);
    expect($files[0]->type)->toBe(SubmissionFileType::Pdf);
    expect($files[0]->order)->toBe(0);
    expect($files[1]->type)->toBe(SubmissionFileType::Image);
    expect($files[1]->order)->toBe(1);
    expect($files[2]->type)->toBe(SubmissionFileType::Image);
    expect($files[2]->order)->toBe(2);
});

test('a non-pdf/image upload is rejected', function () {
    $file = UploadedFile::fake()->create('exam.txt', 100, 'text/plain');

    $this->post(route('exams.submissions.store', $this->exam), ['student_name' => 'Aluno Exemplo', 'files' => [$file]])
        ->assertSessionHasErrors('files.0');

    expect(Submission::count())->toBe(0);
});

test('a draft exam cannot receive submissions', function () {
    $this->exam->update(['status' => ExamStatus::Draft]);

    $file = UploadedFile::fake()->create('exam.pdf', 100, 'application/pdf');

    $this->post(route('exams.submissions.store', $this->exam), ['student_name' => 'Aluno Exemplo', 'files' => [$file]])
        ->assertForbidden();
});

test('another teacher cannot upload a submission', function () {
    $other = User::factory()->create();
    $this->actingAs($other);

    $file = UploadedFile::fake()->create('exam.pdf', 100, 'application/pdf');

    $this->post(route('exams.submissions.store', $this->exam), ['student_name' => 'Aluno Exemplo', 'files' => [$file]])
        ->assertForbidden();
});

test('the computer vision service stores choice answers and updates the submission', function () {
    Queue::fake();

    $question = Question::factory()->singleChoice()->create([
        'exam_id' => $this->exam->id,
        'order' => 0,
        'computer_vision_region' => ['regions' => [
            ['page' => 1, 'x' => 0.08, 'y' => 0.3, 'width' => 0.02, 'height' => 0.01, 'key' => 'choice:0', 'extraction_type' => 'choice'],
            ['page' => 1, 'x' => 0.08, 'y' => 0.32, 'width' => 0.02, 'height' => 0.01, 'key' => 'choice:1', 'extraction_type' => 'choice'],
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

    $submission->refresh();

    expect($submission->status)->toBe(SubmissionStatus::ComputerVisionCompleted);

    $answer = $submission->answers()->first();

    expect($answer)->not->toBeNull();
    expect($answer->raw_text)->toBe('A');
    expect($answer->status)->toBe(SubmissionAnswerStatus::Extracted);

    Http::assertSent(fn ($request) => $request->url() === 'http://python:8000/process');
});

test('the computer vision service marks an answer for manual review when the extraction is uncertain', function () {
    Queue::fake();

    $question = Question::factory()->essay()->create([
        'exam_id' => $this->exam->id,
        'order' => 0,
        'computer_vision_region' => ['regions' => [
            ['page' => 1, 'x' => 0.07, 'y' => 0.3, 'width' => 0.86, 'height' => 0.1, 'key' => 'essay', 'extraction_type' => 'handwritten_text'],
        ]],
    ]);

    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);

    Http::fake([
        'http://python:8000/process' => Http::response([
            'results' => [
                ['question_id' => $question->id, 'raw_text' => 'sister is my favorite', 'status' => 'extracted', 'uncertain' => true],
            ],
        ]),
    ]);

    app(ProcessComputerVisionExtraction::class)->handle($submission);

    $answer = $submission->answers()->first();

    expect($answer->needs_review)->toBeTrue();
});

test('the computer vision payload sends files in submission order', function () {
    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);

    $submission->files()->createMany([
        ['type' => SubmissionFileType::Pdf, 'path' => 'submissions/1/page1.pdf', 'mime_type' => 'application/pdf', 'order' => 0],
        ['type' => SubmissionFileType::Image, 'path' => 'submissions/1/page2.jpg', 'mime_type' => 'image/jpeg', 'order' => 1],
    ]);

    Http::fake([
        'http://python:8000/process' => Http::response([
            'results' => [],
        ]),
    ]);

    app(ProcessComputerVisionExtraction::class)->handle($submission);

    Http::assertSent(function ($request) {
        return data_get($request->data(), 'files') === [
            'submissions/1/page1.pdf',
            'submissions/1/page2.jpg',
        ];
    });
});

test('the computer vision payload includes only files template and answer questions', function () {
    $essay = Question::factory()->essay()->create([
        'exam_id' => $this->exam->id,
        'order' => 0,
        'computer_vision_region' => ['regions' => [
            ['page' => 1, 'x' => 0.07, 'y' => 0.3, 'width' => 0.86, 'height' => 0.1, 'key' => 'essay', 'extraction_type' => 'handwritten_text'],
        ]],
    ]);

    $choice = Question::factory()->singleChoice()->create([
        'exam_id' => $this->exam->id,
        'order' => 1,
        'computer_vision_region' => ['regions' => [
            ['page' => 1, 'x' => 0.08, 'y' => 0.3, 'width' => 0.02, 'height' => 0.01, 'key' => 'choice:0', 'extraction_type' => 'choice'],
        ]],
    ]);

    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);

    Http::fake([
        'http://python:8000/process' => Http::response(['results' => []]),
    ]);

    app(ProcessComputerVisionExtraction::class)->handle($submission);

    Http::assertSent(function ($request) use ($essay, $choice) {
        $questions = data_get($request->data(), 'questions');
        $keys = array_keys($request->data());
        sort($keys);

        return count($questions) === 2
            && (int) $questions[0]['id'] === $essay->id
            && (int) $questions[1]['id'] === $choice->id
            && $keys === ['files', 'questions', 'submission_id', 'template'];
    });
});

test('the computer vision service ignores returned identification and preserves manual name', function () {
    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);
    $originalName = $submission->student_name;

    Http::fake([
        'http://python:8000/process' => Http::response([
            'student_name' => 'Untrusted Name',
            'results' => [],
        ]),
    ]);

    app(ProcessComputerVisionExtraction::class)->handle($submission);

    $submission->refresh();

    expect($submission->student_name)->toBe($originalName);
});

test('reprocessing a submission dispatches the computer vision job', function () {
    Queue::fake();

    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);

    $this->post(route('submissions.reprocess', $submission))
        ->assertRedirect(route('submissions.show', $submission));

    Queue::assertPushed(ProcessSubmissionComputerVisionJob::class);
});

test('the submissions index is accessible to the teacher', function () {
    $this->withoutVite();

    Submission::factory()->create(['exam_id' => $this->exam->id]);

    $this->get(route('exams.submissions.index', $this->exam))->assertOk();
});

test('the submission show page exposes the question expected answer', function () {
    $this->withoutVite();

    $question = Question::factory()->singleChoice()->create([
        'exam_id' => $this->exam->id,
        'order' => 0,
    ]);

    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);

    SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $question->id,
        'raw_text' => 'A',
    ]);

    $this->get(route('submissions.show', $submission))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('exam/Submissions/Show')
            ->has('submission.answers', 1)
            ->where('submission.answers.0.question.metadata.options.0.correct', true)
        );
});

test('a submission cannot be viewed by another teacher', function () {
    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);

    $other = User::factory()->create();
    $this->actingAs($other);

    $this->get(route('submissions.show', $submission))->assertForbidden();
});

test('the computer vision service stores the answer crop path', function () {
    Queue::fake();

    $question = Question::factory()->essay()->create([
        'exam_id' => $this->exam->id,
        'order' => 0,
        'computer_vision_region' => ['regions' => [
            ['page' => 1, 'x' => 0.07, 'y' => 0.3, 'width' => 0.86, 'height' => 0.1, 'key' => 'essay', 'extraction_type' => 'handwritten_text'],
        ]],
    ]);

    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);

    $crop = "submissions/{$submission->id}/crops/{$question->id}_essay.png";

    Http::fake([
        'http://python:8000/process' => Http::response([
            'results' => [
                ['question_id' => $question->id, 'raw_text' => 'resposta', 'confidence' => null, 'status' => 'extracted', 'crop' => $crop],
            ],
        ]),
    ]);

    app(ProcessComputerVisionExtraction::class)->handle($submission);

    expect($submission->answers()->first()->crop_path)->toBe($crop);
});

test('a teacher can view an answer crop image', function () {
    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);
    $question = Question::factory()->essay()->create(['exam_id' => $this->exam->id, 'order' => 0]);

    $answer = SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $question->id,
        'crop_path' => "submissions/{$submission->id}/crops/{$question->id}_essay.png",
    ]);

    Storage::disk('local')->put($answer->crop_path, 'fake-image');

    $this->get(route('submissions.answers.crop', [$submission, $answer]))->assertOk();
});

test('another teacher cannot view an answer crop image', function () {
    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);
    $question = Question::factory()->essay()->create(['exam_id' => $this->exam->id, 'order' => 0]);

    $answer = SubmissionAnswer::factory()->create([
        'submission_id' => $submission->id,
        'question_id' => $question->id,
        'crop_path' => "submissions/{$submission->id}/crops/{$question->id}_essay.png",
    ]);

    Storage::disk('local')->put($answer->crop_path, 'fake-image');

    $other = User::factory()->create();
    $this->actingAs($other);

    $this->get(route('submissions.answers.crop', [$submission, $answer]))->assertForbidden();
});

test('a submission can be created using only the manually entered name', function () {
    Queue::fake();
    $exam = Exam::factory()->create([
        'teacher_id' => $this->teacher->id,
        'status' => ExamStatus::Published,
        'pdf_path' => 'exam-pdfs/1/test.pdf',
    ]);

    $file = UploadedFile::fake()->create('exam.pdf', 100, 'application/pdf');

    $this->post(route('exams.submissions.store', $exam), ['student_name' => '  Aluno Exemplo  ', 'files' => [$file]])
        ->assertRedirect();

    expect($exam->submissions()->first()->student_name)->toBe('Aluno Exemplo');
    Queue::assertPushed(ProcessSubmissionComputerVisionJob::class, 1);
});

test('a teacher can edit the local student name and class', function () {
    $submission = Submission::factory()->create([
        'exam_id' => $this->exam->id,
    ]);

    $this->patch(route('submissions.student.update', $submission), [
        'student_name' => 'Aluno Um',
        'student_class' => '9º Ano B',
    ])->assertRedirect(route('submissions.show', $submission));

    $submission->refresh();

    expect($submission->student_name)->toBe('Aluno Um');
    expect($submission->student_class)->toBe('9º Ano B');
});

test('reprocessing ignores an unrecognized identity', function () {
    Queue::fake();

    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);

    $originalIdentity = $submission->only(['student_name', 'student_class']);

    Http::fake([
        'http://python:8000/process' => Http::response([
            'student_name' => 'Ninguém Conhecido',
            'student_class' => '9º Ano B',
            'results' => [],
        ]),
    ]);

    app(ProcessComputerVisionExtraction::class)->handle($submission);

    $submission->refresh();

    expect($submission->only(['student_name', 'student_class']))->toBe($originalIdentity);

});

test('reprocessing ignores a spoofed identity matching an existing local name', function () {
    Queue::fake();

    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);

    $originalIdentity = $submission->only(['student_name', 'student_class']);

    Http::fake([
        'http://python:8000/process' => Http::response([
            'student_name' => 'Aluno Um',
            'student_class' => '9º Ano B',
            'results' => [],
        ]),
    ]);

    app(ProcessComputerVisionExtraction::class)->handle($submission);

    $submission->refresh();

    expect($submission->only(['student_name', 'student_class']))->toBe($originalIdentity);

});

test('reprocessing ignores a changed class', function () {
    Queue::fake();

    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);

    $originalIdentity = $submission->only(['student_name', 'student_class']);

    Http::fake([
        'http://python:8000/process' => Http::response([
            'student_name' => 'Aluno Um',
            'student_class' => '8º Ano A',
            'results' => [],
        ]),
    ]);

    app(ProcessComputerVisionExtraction::class)->handle($submission);

    $submission->refresh();

    expect($submission->only(['student_name', 'student_class']))->toBe($originalIdentity);

});

test('submission requires a valid manually entered name', function (mixed $name) {
    Queue::fake();

    $this->post(route('exams.submissions.store', $this->exam), [
        'student_name' => $name,
        'files' => [UploadedFile::fake()->create('exam.pdf', 100, 'application/pdf')],
    ])->assertSessionHasErrors('student_name');

    expect(Submission::count())->toBe(0);
    Queue::assertNothingPushed();
})->with([[null], [''], ['   '], [str_repeat('a', 256)], [['invalid']]]);

test('another teacher cannot reprocess or rename a submission', function () {
    Queue::fake();
    $submission = Submission::factory()->create(['exam_id' => $this->exam->id]);
    $originalName = $submission->student_name;
    $this->actingAs(User::factory()->create());

    $this->post(route('submissions.reprocess', $submission))->assertForbidden();
    $this->patch(route('submissions.student.update', $submission), [
        'student_name' => 'Outro nome',
    ])->assertForbidden();

    expect($submission->refresh()->student_name)->toBe($originalName);
    Queue::assertNothingPushed();
});

test('a guest cannot submit an exam', function () {
    auth()->logout();
    Queue::fake();
    $this->post(route('exams.submissions.store', $this->exam), [
        'student_name' => 'Aluno Exemplo',
        'files' => [UploadedFile::fake()->create('exam.pdf', 100, 'application/pdf')],
    ])->assertRedirect(route('login'));
    Queue::assertNothingPushed();
});
