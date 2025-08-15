<?php

namespace App\Services;

use App\Enums\TaskStatus;

class FakeStatusService
{
    public function getStatus(int $taskId): array
    {
        $statuses = [TaskStatus::PROCESSING->value, TaskStatus::COMPLETED->value];
        $status = $statuses[array_rand($statuses)];

        return [
            'id' => $taskId,
            'status' => $status,
        ];
    }
}
