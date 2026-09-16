<?php

namespace App\Models;

use App\Enums\QuestionType;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $exam_id
 * @property QuestionType $type
 * @property string $statement
 * @property float $weight
 * @property int $order
 * @property string|null $grading_notes
 * @property array<string, mixed>|null $metadata
 * @property array<string, mixed>|null $computer_vision_region
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $rendered_html
 * @property-read Exam $exam
 */
#[Fillable(['exam_id', 'type', 'statement', 'weight', 'order', 'grading_notes', 'metadata', 'computer_vision_region'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'weight' => 'float',
            'metadata' => 'array',
            'computer_vision_region' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Exam, $this>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * @return list<string>
     */
    public function correctOptionLetters(): array
    {
        return $this->optionLettersByCorrectness(true);
    }

    /**
     * @return list<string>
     */
    public function wrongOptionLetters(): array
    {
        return $this->optionLettersByCorrectness(false);
    }

    /**
     * @return list<string>
     */
    private function optionLettersByCorrectness(bool $correct): array
    {
        if ($this->type === QuestionType::Essay) {
            return [];
        }

        return array_values(
            collect(array_values($this->metadata['options'] ?? []))
                ->filter(fn (array $option) => (bool) ($option['correct'] ?? false) === $correct)
                ->keys()
                ->map(function (int $index): string {
                    /** @var int<0, 25> $index */
                    return chr(65 + $index);
                })
                ->all(),
        );
    }
}
