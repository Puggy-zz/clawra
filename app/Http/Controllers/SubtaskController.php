<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Subtask;
use App\Services\SubtaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubtaskController extends Controller
{
    public function __construct(private SubtaskService $subtaskService) {}

    /**
     * Display a listing of subtasks.
     */
    public function index(): JsonResponse
    {
        $subtasks = $this->subtaskService->getAllSubtasks();

        return response()->json($subtasks);
    }

    /**
     * Store a newly created subtask.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'agent_id' => 'required|exists:agents,id',
            'name' => 'required|string|max:255',
            'inputs' => 'nullable|array',
            'outputs' => 'nullable|array',
            'status' => 'required|string|in:pending,in-progress,completed,failed',
            'order' => 'required|integer',
        ]);

        $subtask = $this->subtaskService->createSubtask($validatedData);

        return response()->json($subtask, 201);
    }

    /**
     * Display the specified subtask.
     */
    public function show(int $id): JsonResponse
    {
        $subtask = $this->subtaskService->getSubtaskById($id);
        if (! $subtask) {
            return response()->json(['error' => 'Subtask not found'], 404);
        }

        return response()->json($subtask);
    }

    /**
     * Update the specified subtask.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $subtask = $this->subtaskService->getSubtaskById($id);
        if (! $subtask) {
            return response()->json(['error' => 'Subtask not found'], 404);
        }

        $validatedData = $request->validate([
            'task_id' => 'sometimes|exists:tasks,id',
            'agent_id' => 'sometimes|exists:agents,id',
            'name' => 'sometimes|string|max:255',
            'inputs' => 'nullable|array',
            'outputs' => 'nullable|array',
            'status' => 'sometimes|string|in:pending,in-progress,completed,failed',
            'order' => 'sometimes|integer',
        ]);

        $updated = $this->subtaskService->updateSubtask($id, $validatedData);
        if ($updated) {
            return response()->json($this->subtaskService->getSubtaskById($id));
        }

        return response()->json(['error' => 'Failed to update subtask'], 500);
    }

    /**
     * Remove the specified subtask.
     */
    public function destroy(int $id): JsonResponse
    {
        $subtask = $this->subtaskService->getSubtaskById($id);
        if (! $subtask) {
            return response()->json(['error' => 'Subtask not found'], 404);
        }

        $deleted = $this->subtaskService->deleteSubtask($id);
        if ($deleted) {
            return response()->json(['message' => 'Subtask deleted successfully']);
        }

        return response()->json(['error' => 'Failed to delete subtask'], 500);
    }

    /**
     * Create subtasks for a task based on workflow steps.
     */
    public function createForTask(Request $request, int $taskId): JsonResponse
    {
        $validatedData = $request->validate([
            'steps' => 'required|array',
        ]);

        try {
            $subtasks = $this->subtaskService->createSubtasksForTask($taskId, $validatedData['steps']);

            return response()->json($subtasks, 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
