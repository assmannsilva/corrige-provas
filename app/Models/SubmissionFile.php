<?php

namespace App\Models;

use App\Enums\SubmissionFileType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $submission_id
 * @property SubmissionFileType $type
 * @property string $path
 * @property string|null $mime_type
 * @property int $order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Submission $submission
 */
#[Fillable(['submission_id', 'type', 'path', 'mime_type', 'order'])]
class SubmissionFile extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => SubmissionFileType::class,
            'order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Submission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
