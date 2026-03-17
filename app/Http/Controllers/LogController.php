<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function __construct(private LogService $logService) {}

    /**
     * Log a task event.
     */
    public function logTaskEvent(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'subtask_id' => 'required|exists:subtasks,id',
            'agent_id' => 'required|exists:agents,id',
            'log_type' => 'required|string|in:info,warning,error,debug',
            'content' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        $log = $this->logService->logTaskEvent($validatedData);

        return response()->json($log, 201);
    }

    /**
     * Log a review event.
     */
    public function logReviewEvent(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'subtask_id' => 'required|exists:subtasks,id',
            'reviewer_agent_id' => 'required|exists:agents,id',
            'decision' => 'required|string|in:approved,rejected,needs_revision',
            'diff_content' => 'required|string',
            'comments' => 'nullable|string',
        ]);

        $log = $this->logService->logReviewEvent($validatedData);

        return response()->json($log, 201);
    }

    /**
     * Log a heartbeat event.
     */
    public function logHeartbeatEvent(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'timestamp' => 'required|date',
            'decisions' => 'required|array',
            'tasks_queued' => 'required|array',
            'provider_status' => 'required|array',
        ]);

        $log = $this->logService->logHeartbeatEvent($validatedData);

        return response()->json($log, 201);
    }

    /**
     * Get task logs by task ID.
     */
    public function getTaskLogs(int $taskId): JsonResponse
    {
        $logs = $this->logService->getTaskLogsByTaskId($taskId);

        return response()->json($logs);
    }

    /**
     * Get review logs by task ID.
     */
    public function getReviewLogs(int $taskId): JsonResponse
    {
        $logs = $this->logService->getReviewLogsByTaskId($taskId);

        return response()->json($logs);
    }
}
