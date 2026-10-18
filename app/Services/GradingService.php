<?php

namespace App\Services;

use App\Enums\SubmissionAnswerStatus;
use App\Enums\SubmissionStatus;
use App\Models\Question;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Repositories\SubmissionAnswerRepository;
use App\Repositories\SubmissionRepository;
use App\Support\Grading\Graders\GraderRegistry;
use Throwable;

class GradingService
{
    public function __construct(
        private readonly SubmissionRepository $submissionRepository,
        private readonly SubmissionAnswerRepository $submissionAnswerRepository,
        private readonly GraderRegistry $graderRegistry,
    ) {}

    /**
     * @return array{0: float|null, 1: string|null, 2: bool, 3: array<string, mixed>}
     */
    private function evaluate(Question $question, SubmissionAnswer $answer): array
    {
        if ($answer->status !== SubmissionAnswerStatus::Extracted) {
            return [null, 'A extração da resposta falhou ou está incerta; revisão manual necessária.', true, ['method' => 'skipped']];
        }

        $grader = $this->graderRegistry->get($question->type);

        if ($grader === null) {
            return [null, 'Tipo de questão ainda não suportado para correção automática.', true, ['method' => 'skipped']];
        }

        return $grader->grade($question, $answer);
    }

    private function finalize(Submission $submission): void
    {
        $this->refreshTotal($submission);

        $this->submissionRepository->update($submission, [
            'status' => SubmissionStatus::Completed,
            'graded_at' => now(),
        ]);
    }

    private function refreshTotal(Submission $submission): void
    {
        $submission->loadMissing('answers');

        $totalScore = $submission->answers->sum(fn (SubmissionAnswer $answer) => $answer->score ?? 0);

        $this->submissionRepository->update($submission, [
            'total_score' => $totalScore,
        ]);
    }

    public function run(Submission $submission): void
    {
        $this->submissionRepository->update($submission, [
            'status' => SubmissionStatus::Grading,
        ]);

        try {
            $submission->loadMissing(['exam.questions', 'answers.question']);

            foreach ($submission->answers as $answer) {
                $this->gradeAnswer($answer);
            }

            $this->finalize($submission);
        } catch (Throwable $e) {
            $this->submissionRepository->update($submission, [
                'status' => SubmissionStatus::Failed,
            ]);

            throw $e;
        }
    }

    public function gradeAnswer(SubmissionAnswer $answer): SubmissionAnswer
    {
        $answer->loadMissing('question');

        [$score, $feedback, $needsReview, $gradeMetadata] = $this->evaluate($answer->question, $answer);

        return $this->submissionAnswerRepository->updateGrade($answer, [
            'score' => $score,
            'feedback' => $feedback,
            'needs_review' => $needsReview || $answer->needs_review,
            'graded_at' => now(),
            'grade_metadata' => $gradeMetadata,
        ]);
    }

    public function overrideScore(Submission $submission, SubmissionAnswer $answer, float $score, ?string $feedback = null): SubmissionAnswer
    {
        $this->submissionAnswerRepository->updateGrade($answer, [
            'score' => $score,
            'feedback' => $feedback,
            'needs_review' => false,
            'graded_at' => now(),
            'grade_metadata' => ['method' => 'manual'],
        ]);

        $this->refreshTotal($submission);

        return $answer;
    }
}
