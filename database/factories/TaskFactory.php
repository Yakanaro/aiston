<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'status' => TaskStatus::NEW,
            'params' => [
                'audio_url' => 'https://example.com/audio.mp3',
            ],
            'metadata' => [],
            'error' => null,
        ];
    }
}
