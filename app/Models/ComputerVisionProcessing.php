<?php

namespace App\Models;

use App\Enums\ComputerVisionProcessingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $submission_id
 * @property ComputerVisionProcessingStatus $status
 * @property string|null $error_message
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Submission $submission
 */
#[Fillable(['submission_id', 'status', 'error_message', 'started_at', 'completed_at'])]
class ComputerVisionProcessing extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory, HasUuids;

    protected $table = 'computer_vision_processings';

    protected function casts(): array
    {
        return [
            'status' => ComputerVisionProcessingStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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
