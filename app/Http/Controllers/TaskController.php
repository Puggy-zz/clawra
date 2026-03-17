<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    /**
     * Display a listing of tasks.
     */
    public function index(): JsonResponse
    {
        $tasks = $this->taskService->getAllTasks();

        return response()->json($tasks);
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|in:pending,in-progress,completed,failed',
            'workflow_id' => 'required|exists:workflows,id',
            'current_subtask_id' => 'nullable|exists:subtasks,id',
        ]);

        $task = $this->taskService->createTask($validatedData);

        return response()->json($task, 201);
    }

    /**
     * Display the specified task.
     */
    public function show(int $id): JsonResponse
    {
        $task = $this->taskService->getTaskById($id);
        if (! $task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        return response()->json($task);
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $task = $this->taskService->getTaskById($id);
        if (! $task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $validatedData = $request->validate([
            'project_id' => 'sometimes|exists:projects,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|string|in:pending,in-progress,completed,failed',
            'workflow_id' => 'sometimes|exists:workflows,id',
            'current_subtask_id' => 'nullable|exists:subtasks,id',
        ]);

        $updated = $this->taskService->updateTask($id, $validatedData);
        if ($updated) {
            return response()->json($this->taskService->getTaskById($id));
        }

        return response()->json(['error' => 'Failed to update task'], 500);
    }

    /**
     * Remove the specified task.
     */
    public function destroy(int $id): JsonResponse
    {
        $task = $this->taskService->getTaskById($id);
        if (! $task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $deleted = $this->taskService->deleteTask($id);
        if ($deleted) {
            return response()->json(['message' => 'Task deleted successfully']);
        }

        return response()->json(['error' => 'Failed to delete task'], 500);
    }

    /**
     * Create a new task with workflow.
     */
    public function createWithWorkflow(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'workflow_id' => 'required|exists:workflows,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            $task = $this->taskService->createTaskWithWorkflow(
                $validatedData['project_id'],
                $validatedData['workflow_id'],
                $validatedData['name'],
                $validatedData['description']
            );

            return response()->json($task, 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
