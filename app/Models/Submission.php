<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $exam_id
 * @property string|null $student_name
 * @property string|null $student_class
 * @property SubmissionStatus $status
 * @property float|null $total_score
 * @property Carbon|null $submitted_at
 * @property Carbon|null $graded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Exam $exam
 * @property-read Collection<int, SubmissionFile> $files
 * @property-read Collection<int, SubmissionAnswer> $answers
 * @property-read Collection<int, ComputerVisionProcessing> $computerVisionProcessings
 */
#[Fillable(['exam_id', 'student_name', 'student_class', 'status', 'total_score', 'submitted_at', 'graded_at'])]
class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'total_score' => 'float',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
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
     * @return HasMany<SubmissionFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(SubmissionFile::class)->orderBy('order');
    }

    /**
     * @return HasMany<SubmissionAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(SubmissionAnswer::class);
    }

    /**
     * @return HasMany<ComputerVisionProcessing, $this>
     */
    public function computerVisionProcessings(): HasMany
    {
        return $this->hasMany(ComputerVisionProcessing::class);
    }
}
