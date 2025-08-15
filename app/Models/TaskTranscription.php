<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Task|null $task
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTranscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTranscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskTranscription query()
 *
 * @mixin \Eloquent
 */
class TaskTranscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'segments',
    ];

    protected $casts = [
        'segments' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTaskId(): int
    {
        return $this->task_id;
    }

    public function getSegments(): array
    {
        return $this->segments;
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function setSegments(array $segments): self
    {
        $this->segments = $segments;

        return $this;
    }

    public function setTaskId(int $taskId): self
    {
        $this->task_id = $taskId;

        return $this;
    }
}
