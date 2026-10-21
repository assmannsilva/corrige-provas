<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSubmissionComputerVisionJob;
use App\Models\Exam;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Services\GradingService;
use App\Services\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubmissionController extends Controller
{
    public function __construct(
        private readonly SubmissionService $submissionService,
        private readonly GradingService $gradingService,
    ) {}

    public function index(Exam $exam): Response
    {
        $this->authorize('view', $exam);

        $exam->load('submissions.answers');

        return Inertia::render('exam/Submissions/Index', [
            'exam' => $exam,
        ]);
    }

    public function store(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('submit', $exam);

        $validated = $request->validate([
            'student_name' => ['required', 'string', 'max:255'],
            'files' => ['required', 'array', 'min:1', 'max:50'],
            'files.*' => ['required', 'file', 'mimes:pdf,jpeg,png,webp', 'max:51200'],
        ]);

        /** @var list<UploadedFile> $files */
        $files = $request->file('files');

        $this->submissionService->create($exam, $files, trim($validated['student_name']));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Envio realizado.')]);

        return back();
    }

    public function show(Submission $submission): Response
    {
        $this->authorize('view', $submission);

        $submission->load(['exam.questions', 'files', 'answers.question', 'computerVisionProcessings']);

        return Inertia::render('exam/Submissions/Show', [
            'submission' => $submission,
        ]);
    }

    public function reprocess(Submission $submission): RedirectResponse
    {
        $this->authorize('update', $submission);

        ProcessSubmissionComputerVisionJob::dispatch($submission);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reprocessamento iniciado.')]);

        return to_route('submissions.show', $submission);
    }

    public function updateAnswer(Request $request, Submission $submission, SubmissionAnswer $answer): RedirectResponse
    {
        $this->authorize('update', $submission);

        abort_if($answer->submission_id !== $submission->id, 404);

        $answer->loadMissing('question');

        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:'.$answer->question->weight],
            'feedback' => ['nullable', 'string'],
        ]);

        $this->gradingService->overrideScore($submission, $answer, (float) $validated['score'], $validated['feedback'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Nota atualizada.')]);

        return to_route('submissions.show', $submission);
    }

    public function updateStudent(Request $request, Submission $submission): RedirectResponse
    {
        $this->authorize('update', $submission);

        $validated = $request->validate([
            'student_name' => ['required', 'string', 'max:255'],
            'student_class' => ['nullable', 'string', 'max:255'],
        ]);

        $class = isset($validated['student_class']) ? trim($validated['student_class']) : null;

        $this->submissionService->updateStudent($submission, trim($validated['student_name']), $class ?: null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Dados do aluno atualizados.')]);

        return to_route('submissions.show', $submission);
    }

    public function answerCrop(Submission $submission, SubmissionAnswer $answer): BinaryFileResponse
    {
        $this->authorize('view', $submission);

        abort_if($answer->submission_id !== $submission->id, 404);

        $path = $answer->crop_path;

        abort_if(! $path || ! Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path));
    }
}
