<?php

use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use App\Support\Pdf\PdfRenderer;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->teacher = User::factory()->create();
    $this->actingAs($this->teacher);
    $this->exam = Exam::factory()->create(['teacher_id' => $this->teacher->id]);
});

test('generating a pdf persists the file and the computer vision regions', function () {
    $question = Question::factory()->essay()->create([
        'exam_id' => $this->exam->id,
        'order' => 0,
    ]);

    $this->exam->update(['status' => ExamStatus::Published]);

    $this->mock(PdfRenderer::class, function ($mock) use ($question) {
        $mock->shouldReceive('generate')->once()->andReturn('%PDF-1.4 fake-content');
        $mock->shouldReceive('measure')->once()->andReturn([
            'regions' => [
                [
                    'question_id' => $question->id,
                    'key' => 'essay',
                    'page' => 1,
                    'x' => 0.08,
                    'y' => 0.12,
                    'width' => 0.87,
                    'height' => 0.32,
                ],
            ],
        ]);
    });

    $this->post(route('exams.pdf.generate', $this->exam))
        ->assertRedirect(route('exams.show', $this->exam));

    $exam = $this->exam->fresh();

    expect($exam->pdf_path)->not->toBeNull();
    expect($exam->pdf_generated_at)->not->toBeNull();
    expect(Storage::disk('local')->exists($exam->pdf_path))->toBeTrue();

    $region = $question->fresh()->computer_vision_region;

    expect($region['regions'])->toHaveCount(1);
    expect($region['regions'][0]['key'])->toBe('essay');
    expect($region['regions'][0]['extraction_type'])->toBe('handwritten_text');
    expect($region['regions'][0]['x'])->toBeGreaterThanOrEqual(0.0);
    expect($region['regions'][0]['x'])->toBeLessThanOrEqual(1.0);
});

test('pdf preserves line breaks in the question statement', function () {
    $this->exam->update(['status' => ExamStatus::Published]);

    Question::factory()->singleChoice()->create([
        'exam_id' => $this->exam->id,
        'statement' => "Linha 1\nLinha 2",
        'order' => 0,
    ]);

    $html = null;

    $this->mock(PdfRenderer::class, function ($mock) use (&$html) {
        $mock->shouldReceive('generate')->once()->andReturnUsing(function ($generated) use (&$html) {
            $html = $generated;

            return '%PDF-1.4 fake-content';
        });
        $mock->shouldReceive('measure')->once()->andReturn([
            'regions' => [],
        ]);
    });

    $this->post(route('exams.pdf.generate', $this->exam));

    expect($html)->toContain('white-space:pre-wrap');
    expect($html)->toContain("Linha 1\nLinha 2");
});

test('pdf renders choice options with a smaller font', function () {
    $this->exam->update(['status' => ExamStatus::Published]);

    Question::factory()->singleChoice()->create([
        'exam_id' => $this->exam->id,
        'order' => 0,
    ]);

    $html = null;

    $this->mock(PdfRenderer::class, function ($mock) use (&$html) {
        $mock->shouldReceive('generate')->once()->andReturnUsing(function ($generated) use (&$html) {
            $html = $generated;

            return '%PDF-1.4 fake-content';
        });
        $mock->shouldReceive('measure')->once()->andReturn([
            'regions' => [],
        ]);
    });

    $this->post(route('exams.pdf.generate', $this->exam));

    expect($html)->toContain('<span style="font-size:11px;">Option A</span>');
});

test('a draft exam cannot generate a pdf', function () {
    $this->post(route('exams.pdf.generate', $this->exam))->assertForbidden();

    expect($this->exam->fresh()->pdf_path)->toBeNull();
});

test('pdf includes fiducial alignment markers', function () {
    $this->exam->update(['status' => ExamStatus::Published]);

    Question::factory()->singleChoice()->create([
        'exam_id' => $this->exam->id,
        'order' => 0,
    ]);

    $html = null;

    $this->mock(PdfRenderer::class, function ($mock) use (&$html) {
        $mock->shouldReceive('generate')->once()->andReturnUsing(function ($generated) use (&$html) {
            $html = $generated;

            return '%PDF-1.4 fake-content';
        });
        $mock->shouldReceive('measure')->once()->andReturn([
            'regions' => [],
        ]);
    });

    $this->post(route('exams.pdf.generate', $this->exam));

    expect($html)->toContain('.fiducial');
    expect($html)->toContain('addMarkers');
    expect($html)->toContain("left: '6mm', top: '6mm'");
    expect($html)->toContain("left: '198mm', top: '285mm'");
});

test('an exam cannot generate a pdf for another teacher', function () {
    $this->exam->update(['status' => ExamStatus::Published]);

    $other = User::factory()->create();
    $this->actingAs($other);

    $this->post(route('exams.pdf.generate', $this->exam))->assertForbidden();
});

test('downloading is forbidden when no pdf has been generated', function () {
    $this->exam->update(['status' => ExamStatus::Published]);

    $this->get(route('exams.pdf.download', $this->exam))->assertForbidden();
});

test('downloading returns the generated pdf', function () {
    Storage::disk('local')->put('exam-pdfs/1/test.pdf', '%PDF-1.4 content');

    $this->exam->update([
        'status' => ExamStatus::Published,
        'pdf_path' => 'exam-pdfs/1/test.pdf',
    ]);

    $this->get(route('exams.pdf.download', $this->exam))
        ->assertOk()
        ->assertDownload();
});

test('an exam cannot be downloaded by another teacher', function () {
    $this->exam->update([
        'status' => ExamStatus::Published,
        'pdf_path' => 'exam-pdfs/1/test.pdf',
    ]);

    $other = User::factory()->create();
    $this->actingAs($other);

    $this->get(route('exams.pdf.download', $this->exam))->assertForbidden();
});
