<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Jobs\CheckTaskStatusJob;
use App\Models\ApiToken;
use App\Models\Task;
use App\Models\TaskQaEvaluation;
use App\Models\TaskTranscription;
use App\Services\FakeLlmService;
use App\Services\FakeStatusService;
use App\Services\FakeTranscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_task(): void
    {
        Bus::fake();
        $token = 'test-token-123';
        ApiToken::create([
            'name' => 'test',
            'token_hash' => hash('sha256', $token),
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ])->json('POST', '/api/tasks', [
            'audio_url' => 'https://example.com/audio.mp3',
            'metadata' => [],
        ]);

        $response->assertStatus(201);

        $taskId = $response->json('id');

        $this->assertDatabaseHas('tasks', [
            'id' => $taskId,
            'status' => TaskStatus::NEW->value,
        ]);

        Bus::assertDispatched(CheckTaskStatusJob::class, function (CheckTaskStatusJob $job) use ($taskId) {
            return $job->taskId === $taskId;
        });
    }

    public function test_without_token(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->json('POST', '/api/tasks', [
            'audio_url' => 'https://example.com/audio.mp3',
            'metadata' => [],
        ]);

        $response->assertStatus(401);
    }

    public function test_invalid_token(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer invalid-token',
        ])->json('POST', '/api/tasks', [
            'audio_url' => 'https://example.com/audio.mp3',
            'metadata' => [],
        ]);

        $response->assertStatus(401);
    }

    public function test_show_task(): void
    {
        $token = 'test-token-123';
        ApiToken::create([
            'name' => 'test',
            'token_hash' => hash('sha256', $token),
        ]);

        $task = Task::factory()->create([
            'status' => TaskStatus::COMPLETED->value,
        ]);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ])->json('GET', "/api/tasks/{$task->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $task->id,
            'status' => TaskStatus::COMPLETED->value,
        ]);
    }

    public function test_scheduler_dispatches_jobs_for_pending_tasks(): void
    {
        Bus::fake();

        Task::factory()->create(['status' => TaskStatus::NEW->value]);
        Task::factory()->create(['status' => TaskStatus::PROCESSING->value]);
        Task::factory()->create(['status' => TaskStatus::COMPLETED->value]);

        Task::query()
            ->whereIn('status', [TaskStatus::NEW->value, TaskStatus::PROCESSING->value])
            ->orderBy('id')
            ->pluck('id')
            ->each(function ($id) {
                CheckTaskStatusJob::dispatch((int) $id);
            });

        Bus::assertDispatched(CheckTaskStatusJob::class, 2);
    }

    public function test_job_reschedules_when_still_processing(): void
    {
        $task = Task::factory()->create([
            'status' => TaskStatus::NEW->value,
            'params' => ['audio_url' => 'https://example.com/a.mp3'],
            'metadata' => [],
        ]);

        $this->app->bind(FakeStatusService::class, function () {
            return new class extends FakeStatusService
            {
                public function getStatus(int $taskId): array
                {
                    return ['id' => $taskId, 'status' => TaskStatus::PROCESSING->value];
                }
            };
        });

        Queue::fake();

        $job = new CheckTaskStatusJob($task->id);
        $job->handle(
            app(FakeStatusService::class),
            app(FakeTranscriptionService::class),
            app(FakeLlmService::class),
        );

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::PROCESSING->value,
        ]);

        Queue::assertPushed(CheckTaskStatusJob::class);
    }

    public function test_job_completes_and_saves_transcription_and_evaluation(): void
    {
        $task = Task::factory()->create([
            'status' => TaskStatus::NEW->value,
            'params' => ['audio_url' => 'https://example.com/a.mp3'],
            'metadata' => [],
        ]);

        $this->app->bind(FakeStatusService::class, function () {
            return new class extends FakeStatusService
            {
                public function getStatus(int $taskId): array
                {
                    return ['id' => $taskId, 'status' => TaskStatus::COMPLETED->value];
                }
            };
        });

        $job = new CheckTaskStatusJob($task->id);
        $job->handle(
            app(FakeStatusService::class),
            app(FakeTranscriptionService::class),
            app(FakeLlmService::class),
        );

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::COMPLETED->value,
        ]);

        $this->assertDatabaseHas('task_transcriptions', [
            'task_id' => $task->id,
        ]);

        $this->assertDatabaseHas('task_qa_evaluations', [
            'task_id' => $task->id,
        ]);

        $transcription = TaskTranscription::query()->where('task_id', $task->id)->first();
        $this->assertNotNull($transcription);
        $this->assertIsArray($transcription->segments);
        $this->assertNotEmpty($transcription->segments);
        $this->assertArrayHasKey('speaker', $transcription->segments[0]);

        $evaluation = TaskQaEvaluation::query()->where('task_id', $task->id)->first();
        $this->assertNotNull($evaluation);
        $this->assertIsInt($evaluation->overall_score);
        $this->assertIsArray($evaluation->details);
    }
}
