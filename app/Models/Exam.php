<?php

namespace App\Models;

use App\Enums\ExamStatus;
use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $teacher_id
 * @property string $title
 * @property string|null $instructions
 * @property ExamStatus $status
 * @property string|null $pdf_path
 * @property Carbon|null $pdf_generated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $teacher
 * @property-read Collection<int, Question> $questions
 * @property-read int $total_points
 * @property-read bool $pdf_ready
 */
#[Fillable(['teacher_id', 'title', 'instructions', 'status', 'pdf_path', 'pdf_generated_at'])]
#[Appends(['total_points', 'pdf_ready'])]
class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ExamStatus::class,
            'pdf_generated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    /**
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function isDraft(): bool
    {
        return $this->status === ExamStatus::Draft;
    }

    public function isPublished(): bool
    {
        return $this->status === ExamStatus::Published;
    }

    public function isArchived(): bool
    {
        return $this->status === ExamStatus::Archived;
    }

    public function getTotalPointsAttribute(): int
    {
        return (int) $this->questions()->sum('weight');
    }

    public function isPdfReady(): bool
    {
        return $this->pdf_path !== null;
    }

    public function getPdfReadyAttribute(): bool
    {
        return $this->isPdfReady();
    }
}
