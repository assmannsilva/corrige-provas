<?php

namespace App\Models;

use App\Enums\SubmissionAnswerStatus;
use Database\Factories\SubmissionAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $submission_id
 * @property int $question_id
 * @property string|null $raw_text
 * @property string|null $crop_path
 * @property float|null $score
 * @property string|null $feedback
 * @property bool $needs_review
 * @property Carbon|null $graded_at
 * @property array<string, mixed>|null $grade_metadata
 * @property SubmissionAnswerStatus $status
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Submission $submission
 * @property-read Question $question
 */
#[Fillable(['submission_id', 'question_id', 'raw_text', 'crop_path', 'score', 'feedback', 'needs_review', 'graded_at', 'grade_metadata', 'status', 'metadata'])]
class SubmissionAnswer extends Model
{
    /** @use HasFactory<SubmissionAnswerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => SubmissionAnswerStatus::class,
            'score' => 'float',
            'needs_review' => 'boolean',
            'graded_at' => 'datetime',
            'metadata' => 'array',
            'grade_metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Submission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
