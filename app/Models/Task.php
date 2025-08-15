<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read \App\Models\TaskQaEvaluation|null $evaluation
 * @property-read \App\Models\TaskTranscription|null $transcription
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task query()
 *
 * @property TaskStatus $status
 *
 * @method static \Database\Factories\TaskFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'params',
        'metadata',
        'error',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'params' => 'array',
        'metadata' => 'array',
    ];

    public function transcription(): HasOne
    {
        return $this->hasOne(TaskTranscription::class);
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(TaskQaEvaluation::class);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getTranscription(): ?TaskTranscription
    {
        return $this->transcription;
    }

    public function getEvaluation(): ?TaskQaEvaluation
    {
        return $this->evaluation;
    }

    public function setStatus(TaskStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function setError(?string $error): self
    {
        $this->error = $error;

        return $this;
    }

    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }
}
