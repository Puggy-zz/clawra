<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{
    public function __construct(protected SubtaskService $subtaskService) {}

    public function getAllTasks(): Collection
    {
        return Task::query()->with(['project', 'workflow', 'recommendedAgent', 'currentSubtask', 'subtasks'])->latest()->get();
    }

    public function getTaskById(int $id): ?Task
    {
        return Task::query()->with(['project', 'workflow', 'recommendedAgent', 'currentSubtask', 'subtasks'])->find($id);
    }

    public function createTask(array $data): Task
    {
        $task = Task::query()->create($data);

        return $task->refresh();
    }

    public function updateTask(int $id, array $data): bool
    {
        $task = $this->getTaskById($id);

        return $task instanceof Task ? $task->update($data) : false;
    }

    public function deleteTask(int $id): bool
    {
        $task = $this->getTaskById($id);

        return $task instanceof Task ? (bool) $task->delete() : false;
    }

    public function createTaskWithWorkflow(int $projectId, int $workflowId, string $name, ?string $description = null, ?int $recommendedAgentId = null, ?int $projectConversationId = null): Task
    {
        $project = Project::query()->find($projectId);
        $workflow = Workflow::query()->find($workflowId);

        if (! $project instanceof Project || ! $workflow instanceof Workflow) {
            throw new \InvalidArgumentException('Invalid project or workflow ID');
        }

        $task = Task::query()->create([
            'project_id' => $projectId,
            'project_conversation_id' => $projectConversationId,
            'name' => $name,
            'description' => $description,
            'status' => 'pending',
            'workflow_id' => $workflowId,
            'recommended_agent_id' => $recommendedAgentId,
        ]);

        $subtasks = $this->subtaskService->createSubtasksForTask($task->id, $workflow->steps ?? [], $recommendedAgentId);
        $firstSubtask = $subtasks[0] ?? null;

        if ($firstSubtask !== null) {
            $task->update(['current_subtask_id' => $firstSubtask->id]);
        }

        return $this->getTaskById($task->id) ?? $task;
    }
}
