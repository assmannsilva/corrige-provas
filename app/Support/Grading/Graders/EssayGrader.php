<?php

namespace App\Support\Grading\Graders;

use App\Models\Question;
use App\Models\SubmissionAnswer;
use App\Support\Grading\GradingClient;

final class EssayGrader implements Grader
{
    public function __construct(
        private readonly GradingClient $gradingClient,
    ) {}

    private function systemPrompt(): string
    {
        return <<<'TXT'
Você é um assistente de correção de provas.
Avalie a resposta do aluno em relação ao enunciado, à resposta esperada e aos critérios de correção.

Regras:
- Não invente informação que não esteja na resposta do aluno.
- Não corrija a resposta do aluno antes de avaliá-la.
- Considere equivalência semântica: aceite respostas corretas escritas com outras palavras.
- Atribua apenas os pontos suportados pelos critérios de correção.
- A nota deve ser um número float de até 1 casa decimal entre 0 e a nota máxima.
- Não gere comentários motivacionais, comportamentais ou psicológicos.
- Quando houver incerteza relevante que afete a nota, marque needs_review como true.

Responda APENAS com um JSON no seguinte formato:
{"score": <numero>, "feedback": "<texto>", "needs_review": <boolean>}
TXT;
    }

    private function userPrompt(Question $question, string $raw): string
    {
        $expected = (string) ($question->metadata['expected_answer'] ?? '');
        $criteria = trim((string) $question->grading_notes);
        $criteria = $criteria === '' ? 'Nenhum critério adicional informado.' : $criteria;

        return <<<TXT
Enunciado:
{$question->statement}

Nota máxima:
{$question->weight}

Resposta esperada:
{$expected}

Critérios de correção:
{$criteria}

Resposta do aluno:
{$raw}
TXT;
    }

    /**
     * @return array{0: float|null, 1: string|null, 2: bool, 3: array<string, mixed>}
     */
    public function grade(Question $question, SubmissionAnswer $answer): array
    {
        $raw = trim((string) $answer->raw_text);

        if ($raw === '') {
            return [0, 'Resposta em branco.', false, ['method' => 'deterministic']];
        }

        $metadata = [
            'method' => 'llm',
            'provider' => 'openrouter',
            'model' => (string) config('services.grading.model'),
        ];

        $result = $this->gradingClient->grade(
            $this->systemPrompt(),
            $this->userPrompt($question, $raw),
        );

        $score = $result['score'] ?? null;
        $feedback = isset($result['feedback']) ? (string) $result['feedback'] : null;
        $needsReview = (bool) ($result['needs_review'] ?? false);

        if (! is_numeric($score) || (float) $score < 0 || (float) $score > $question->weight) {
            $score = null;
            $needsReview = true;
            $feedback = $feedback ?: 'Não foi possível determinar a nota automaticamente.';
        } else {
            $score = round((float) $score, 1);
        }

        if ($needsReview) {
            $score = null;
        }

        return [$score, $feedback, $needsReview, $metadata];
    }
}
