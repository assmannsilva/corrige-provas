<?php

use App\Http\Controllers\ExamController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('exams.index') : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('exams', ExamController::class);

    Route::post('exams/{exam}/pdf/generate', [ExamController::class, 'generatePdf'])
        ->name('exams.pdf.generate');
    Route::get('exams/{exam}/pdf/download', [ExamController::class, 'downloadPdf'])
        ->name('exams.pdf.download');

    Route::post('exams/{exam}/questions/reorder', [QuestionController::class, 'reorder'])
        ->name('exams.questions.reorder');
    Route::resource('exams.questions', QuestionController::class)
        ->only(['store', 'update', 'destroy']);

    Route::get('exams/{exam}/submissions', [SubmissionController::class, 'index'])
        ->name('exams.submissions.index');
    Route::post('exams/{exam}/submissions', [SubmissionController::class, 'store'])
        ->name('exams.submissions.store');
    Route::get('submissions/{submission}', [SubmissionController::class, 'show'])
        ->name('submissions.show');
    Route::get('submissions/{submission}/answers/{answer}/crop', [SubmissionController::class, 'answerCrop'])
        ->name('submissions.answers.crop');
    Route::post('submissions/{submission}/reprocess', [SubmissionController::class, 'reprocess'])
        ->name('submissions.reprocess');
    Route::patch('submissions/{submission}/answers/{answer}', [SubmissionController::class, 'updateAnswer'])
        ->name('submissions.answers.update');
    Route::patch('submissions/{submission}/student', [SubmissionController::class, 'updateStudent'])
        ->name('submissions.student.update');
});

require __DIR__.'/settings.php';
