<?php

namespace App\Repositories\Task;

use App\Models\Task;

class TaskRepository
{
    public function __construct(
        private ?Task $task,
    ) {}

    public function findOrFail(int $taskId): Task
    {
        return $this->task::query()
            ->with(['transcription', 'evaluation'])
            ->where('id', $taskId)
            ->firstOrFail();
    }

    public function create(Task $task): void
    {
        $task->save();
    }
}
