<?php

namespace App\Jobs;

use App\Enums\TaskStatus;
use App\Models\ExternalLog;
use App\Models\TaskQaEvaluation;
use App\Models\TaskTranscription;
use App\Repositories\Task\TaskRepository;
use App\Services\FakeLlmService;
use App\Services\FakeStatusService;
use App\Services\FakeTranscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class CheckTaskStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $taskId,
    ) {}

    public function handle(FakeStatusService $statusService, FakeTranscriptionService $transcriptionService, FakeLlmService $llmService): void
    {
        $task = app(TaskRepository::class)->findOrFail($this->taskId);

        try {
            ExternalLog::create([
                'task_id' => $task->getId(),
                'service' => 'status',
                'direction' => 'request',
                'payload' => ['task_id' => $task->getId()],
                'message' => null,
            ]);

            $result = $statusService->getStatus($task->getId());

            ExternalLog::create([
                'task_id' => $task->getId(),
                'service' => 'status',
                'direction' => 'response',
                'payload' => $result,
                'message' => null,
            ]);

            if (($result['status'] ?? TaskStatus::PROCESSING->value) === TaskStatus::COMPLETED->value) {
                $task->setStatus(TaskStatus::PROCESSING)->save();

                $segments = $transcriptionService->process((string) ($task->getParams()['audio_url'] ?? ''));

                (new TaskTranscription)
                    ->setTaskId($task->getId())
                    ->setSegments($segments)
                    ->save();

                $eval = $llmService->evaluate($segments);

                (new TaskQaEvaluation)
                    ->setTaskId($task->getId())
                    ->setOverallScore($eval['overall_score'])
                    ->setDetails($eval['details'])
                    ->save();

                $task->setStatus(TaskStatus::COMPLETED)->save();
            } else {
                $task->setStatus(TaskStatus::PROCESSING)->save();
                self::dispatch($task->getId())->delay(now()->addMinutes(5));
            }
        } catch (Throwable $e) {
            ExternalLog::create([
                'task_id' => $task->getId(),
                'service' => 'status',
                'direction' => 'error',
                'payload' => ['exception' => get_class($e)],
                'message' => $e->getMessage(),
            ]);
            $task->setStatus(TaskStatus::ERROR)->setError($e->getMessage())->save();
        }
    }
}
