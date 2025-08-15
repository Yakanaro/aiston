<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Jobs\CheckTaskStatusJob;
use App\Models\Task;
use App\Repositories\Task\TaskRepository;
use Illuminate\Http\JsonResponse;
use Throwable;

class TaskController extends Controller
{
    public function __construct(
        private TaskRepository $taskRepository,
    ) {}

    public function store(StoreTaskRequest $request): JsonResponse
    {
        try {
            $task = (new Task)
                ->setStatus(TaskStatus::NEW)
                ->setParams([
                    'audio_url' => $request->getAudioUrl(),
                ])
                ->setMetadata($request->getMetadata());

            $this->taskRepository->create($task);

            dispatch(new CheckTaskStatusJob($task->getId()));

            return response()->json(['id' => $task->getId(), 'status' => $task->getStatus()], 201);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $task = $this->taskRepository->findOrFail($id);

            return response()->json([
                'id' => $task->getId(),
                'status' => $task->getStatus(),
                'params' => $task->getParams(),
                'metadata' => $task->getMetadata(),
                'error' => $task->getError(),
                'transcription' => $task->getTranscription()?->segments,
                'evaluation' => $task->getEvaluation() ? [
                    'overall_score' => $task->getEvaluation()->getOverallScore(),
                    'details' => $task->getEvaluation()->getDetails(),
                ] : null,
            ]);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
