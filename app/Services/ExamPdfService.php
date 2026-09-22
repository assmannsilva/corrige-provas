<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Models\Exam;
use App\Renderers\RendererRegistry;
use App\Repositories\ExamRepository;
use App\Repositories\QuestionRepository;
use App\Support\Pdf\PdfRenderer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExamPdfService
{
    public function __construct(
        private readonly ExamRepository $examRepository,
        private readonly QuestionRepository $questionRepository,
        private readonly RendererRegistry $rendererRegistry,
        private readonly PdfRenderer $pdfRenderer,
    ) {}

    public function generate(Exam $exam): Exam
    {
        $html = $this->buildHtml($exam);

        $pdf = $this->pdfRenderer->generate($html);
        $measurements = $this->pdfRenderer->measure($html);
        $regions = $measurements['regions'];

        $path = 'exam-pdfs/'.$exam->id.'/'.Str::uuid().'.pdf';
        Storage::disk('local')->put($path, $pdf);

        DB::transaction(function () use ($exam, $path, $regions) {
            $this->examRepository->update($exam, [
                'pdf_path' => $path,
                'pdf_generated_at' => now(),
            ]);

            foreach ($this->groupRegionsByQuestion($regions) as $questionId => $questionRegions) {
                $question = $this->questionRepository->findById($questionId);

                if ($question === null || $question->exam_id !== $exam->id) {
                    continue;
                }

                $extractionType = $this->extractionType($question->type);
                $questionRegions = array_map(
                    fn (array $region) => [...$region, 'extraction_type' => $extractionType],
                    $questionRegions,
                );

                $this->questionRepository->update($question, [
                    'computer_vision_region' => ['regions' => $questionRegions],
                ]);
            }
        });

        return $exam->refresh();
    }

    private function buildHtml(Exam $exam): string
    {
        $title = e($exam->title);

        $instructionsHtml = $exam->instructions
            ? '<div style="font-size:11px;text-align:center;border:1px solid #000000;padding:6px;margin:0 0 6px;white-space:pre-wrap;">'.e($exam->instructions).'</div>'
            : '';

        $questions = '';

        foreach ($this->questionRepository->findByExam($exam->id) as $index => $question) {
            $renderer = $this->rendererRegistry->get($question->type);
            $answerHtml = $renderer->renderPdf($question);
            $number = $index + 1;
            $statement = e($question->statement);

            $questions .= <<<HTML
            <div class="cell">
                <div data-question-id="{$question->id}">
                    <div style="font-size:12px;line-height:1.4;margin-bottom:6px;white-space:pre-wrap;"><span style="font-weight:bold;">{$number})</span> {$statement}</div>
                    {$answerHtml}
                </div>
            </div>
            HTML;
        }

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                @page { size: A4; margin: 0; }
                * { box-sizing: border-box; }
                html, body { margin: 0; padding: 0; }
                body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #111827; }
                #source { width: 180mm; }
                .cell { padding-bottom: 10px; }
                .page { width: 210mm; height: 297mm; padding: 15mm; overflow: hidden; position: relative; page-break-after: always; }
                .page:last-child { page-break-after: auto; }
                .fiducial { position: absolute; width: 6mm; height: 6mm; background: #000000; }
            </style>
        </head>
        <body>
            <div id="pages"></div>
            <div id="source">
                <div class="cell">
                    <h1 style="text-align:center;font-size:14px;margin:0 0 6px;">{$title}</h1>
                    <div style="font-size:11px;margin:0 0 6px;">Aluno(a): <span style="display:inline-block;width:110mm;border-bottom:1px solid #000000;height:24px;"></span> &nbsp;&nbsp;&nbsp; Turma: <span style="display:inline-block;width:40mm;border-bottom:1px solid #000000;height:24px;vertical-align:bottom;"></span></div>
                    {$instructionsHtml}
                </div>
                {$questions}
            </div>
            <script>
                (function () {
                    var pageContentHeight = 267 * 96 / 25.4;
                    var source = document.getElementById('source');
                    var pages = document.getElementById('pages');
                    var cells = Array.prototype.slice.call(source.children);
                    var current = null;
                    var used = 0;

                    function addMarkers(page) {
                        [
                            { left: '6mm', top: '6mm' },
                            { left: '198mm', top: '6mm' },
                            { left: '198mm', top: '285mm' },
                            { left: '6mm', top: '285mm' },
                        ].forEach(function (position) {
                            var marker = document.createElement('div');
                            marker.className = 'fiducial';
                            marker.style.left = position.left;
                            marker.style.top = position.top;
                            page.appendChild(marker);
                        });
                    }

                    cells.forEach(function (cell) {
                        var height = cell.offsetHeight;

                        if (!current || (used > 0 && used + height > pageContentHeight)) {
                            current = document.createElement('div');
                            current.className = 'page';
                            pages.appendChild(current);
                            addMarkers(current);
                            used = 0;
                        }

                        current.appendChild(cell);
                        used += height;
                    });

                    source.remove();
                })();
            </script>
        </body>
        </html>
        HTML;
    }

    /**
     * @param  list<array{question_id: int|null, key: string|null, page: int, x: float, y: float, width: float, height: float}>  $regions
     * @return array<int, list<array{page: int, x: float, y: float, width: float, height: float, key: string|null}>>
     */
    private function groupRegionsByQuestion(array $regions): array
    {
        $grouped = [];

        foreach ($regions as $region) {
            if (($region['question_id'] ?? null) === null) {
                continue;
            }

            $grouped[(int) $region['question_id']][] = [
                'page' => $region['page'],
                'x' => $region['x'],
                'y' => $region['y'],
                'width' => $region['width'],
                'height' => $region['height'],
                'key' => $region['key'] ?? null,
            ];
        }

        return $grouped;
    }

    private function extractionType(QuestionType $type): string
    {
        return match ($type) {
            QuestionType::Essay => 'handwritten_text',
            QuestionType::SingleChoice, QuestionType::MultipleChoice => 'choice',
        };
    }
}
