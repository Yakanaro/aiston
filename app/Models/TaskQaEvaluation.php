<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Task|null $task
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskQaEvaluation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskQaEvaluation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskQaEvaluation query()
 *
 * @mixin \Eloquent
 */
class TaskQaEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'overall_score',
        'details',
    ];

    protected $casts = [
        'overall_score' => 'integer',
        'details' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function getOverallScore(): int
    {
        return $this->overall_score;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setTaskId(int $taskId): self
    {
        $this->task_id = $taskId;

        return $this;
    }

    public function setOverallScore(int $overallScore): self
    {
        $this->overall_score = $overallScore;

        return $this;
    }

    public function setDetails(array $details): self
    {
        $this->details = $details;

        return $this;
    }
}
