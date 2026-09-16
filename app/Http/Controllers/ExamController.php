<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\QuestionTypes\QuestionTypeRegistry;
use App\Renderers\RendererRegistry;
use App\Services\ExamPdfService;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExamService $examService,
        private readonly ExamPdfService $examPdfService,
        private readonly QuestionTypeRegistry $typeRegistry,
        private readonly RendererRegistry $rendererRegistry,
    ) {}

    public function index(Request $request): Response
    {
        $exams = $this->examService->findByTeacherPaginated(
            $request->user()->id,
            10,
            $request->query('search'),
            $request->query('status'),
        );

        return Inertia::render('exam/Index', [
            'exams' => $exams,
            'filters' => [
                'search' => $request->query('search'),
                'status' => $request->query('status'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('exam/Builder', [
            'exam' => null,
            'questionTypes' => $this->typeRegistry->toArray(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
        ]);

        $exam = $this->examService->create($request->user()->id, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Prova criada.')]);

        return to_route('exams.edit', $exam);
    }

    public function show(Exam $exam): Response
    {
        $this->authorize('view', $exam);

        $exam->load('questions');

        $exam->questions->each(
            fn ($question) => $question->rendered_html = $this->rendererRegistry
                ->get($question->type)
                ->renderHtml($question)
        );

        return Inertia::render('exam/Show', [
            'exam' => $exam,
            'questionTypes' => $this->typeRegistry->toArray(),
        ]);
    }

    public function edit(Exam $exam): Response|RedirectResponse
    {
        $this->authorize('view', $exam);

        if (! $exam->isDraft()) {
            return to_route('exams.show', $exam);
        }

        return Inertia::render('exam/Builder', [
            'exam' => $exam->load('questions'),
            'questionTypes' => $this->typeRegistry->toArray(),
        ]);
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('view', $exam);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:published,archived'],
        ]);

        if (isset($validated['title'])) {
            $this->examService->update($exam, [
                'title' => $validated['title'],
                'instructions' => $validated['instructions'] ?? null,
            ]);
        }

        if (($validated['status'] ?? null) === 'published') {
            $this->examService->publish($exam);
        } elseif (($validated['status'] ?? null) === 'archived') {
            $this->examService->archive($exam);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Prova atualizada.')]);

        if ($validated['status'] ?? null) {
            return to_route('exams.show', $exam);
        }

        return to_route('exams.edit', $exam);
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorize('delete', $exam);

        $this->examService->delete($exam);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Prova excluída.')]);

        return to_route('exams.index');
    }

    public function generatePdf(Exam $exam): RedirectResponse
    {
        $this->authorize('generatePdf', $exam);

        $this->examPdfService->generate($exam);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('PDF da prova gerado.')]);

        return to_route('exams.show', $exam);
    }

    public function downloadPdf(Exam $exam): BinaryFileResponse
    {
        $this->authorize('downloadPdf', $exam);

        return response()->download(
            Storage::disk('local')->path($exam->pdf_path),
            Str::slug($exam->title).'.pdf',
        );
    }
}
