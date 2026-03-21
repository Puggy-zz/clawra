<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class ProjectService
{
    public function getAllProjects(): Collection
    {
        return Project::query()->with(['tasks.workflow', 'tasks.currentSubtask'])->orderBy('name')->get();
    }

    public function getProjectById(int $id): ?Project
    {
        return Project::query()->with(['tasks.workflow', 'tasks.currentSubtask'])->find($id);
    }

    public function createProject(array $data): Project
    {
        $project = Project::query()->create([
            ...$data,
            'state_document' => $data['state_document'] ?? $this->defaultStateDocument($data['description'] ?? null),
            'current_intent' => $data['current_intent'] ?? 'Awaiting coordinator input',
        ]);

        return $project->refresh();
    }

    public function updateProject(int $id, array $data): bool
    {
        $project = $this->getProjectById($id);

        if (! $project instanceof Project) {
            return false;
        }

        if (array_key_exists('state_document', $data) && $data['state_document'] === null) {
            $data['state_document'] = $this->defaultStateDocument($project->description);
        }

        return $project->update($data);
    }

    public function deleteProject(int $id): bool
    {
        $project = $this->getProjectById($id);

        return $project instanceof Project ? (bool) $project->delete() : false;
    }

    public function ensureInboxProject(): Project
    {
        $project = Project::query()->firstOrCreate(
            ['name' => 'Inbox'],
            [
                'description' => 'Holding area for tasks and ideas not yet assigned to a specific project.',
                'goals' => null,
                'status' => 'active',
                'state_document' => $this->defaultStateDocument('Inbox'),
                'current_intent' => 'Waiting for a new orchestration request',
            ]
        );

        return $project->refresh();
    }

    public function recordTask(Project $project, Task $task, string $message, array $context = []): void
    {
        $stateDocument = $project->state_document ?? $this->defaultStateDocument($project->description);
        $stateDocument['current_working_intent'] = $message;
        $stateDocument['outstanding_tasks'][] = [
            'id' => $task->id,
            'name' => $task->name,
            'status' => $task->status,
            'workflow' => $task->workflow?->name,
            'current_subtask' => $task->currentSubtask?->name,
        ];
        $stateDocument['log'][] = [
            'type' => 'coordinator',
            'message' => $message,
            'context' => $this->summarizeContext($context),
            'timestamp' => now()->toISOString(),
        ];
        $stateDocument['updated_at'] = now()->toISOString();

        $project->update([
            'state_document' => $stateDocument,
            'current_intent' => $message,
        ]);
    }

    public function getPendingTaskDraft(Project $project): ?array
    {
        $stateDocument = $project->state_document ?? [];
        $draft = $stateDocument['pending_task_draft'] ?? null;

        return is_array($draft) ? $draft : null;
    }

    public function storePendingTaskDraft(Project $project, array $draft): void
    {
        $stateDocument = $project->state_document ?? $this->defaultStateDocument($project->description);
        $stateDocument['pending_task_draft'] = $draft;
        $stateDocument['current_working_intent'] = $draft['summary'] ?? $draft['title'] ?? 'Pending task draft';
        $stateDocument['log'][] = [
            'type' => 'task_draft',
            'message' => $draft['summary'] ?? $draft['title'] ?? 'Pending task draft',
            'context' => [
                'task_type' => $draft['workflow_type'] ?? 'general',
                'recommended_agent' => $draft['recommended_agent'] ?? null,
                'recommended_agent_id' => $draft['recommended_agent_id'] ?? null,
                'clarifying_questions' => $draft['clarifying_questions'] ?? [],
            ],
            'timestamp' => now()->toISOString(),
        ];
        $stateDocument['updated_at'] = now()->toISOString();

        $project->update([
            'state_document' => $stateDocument,
            'current_intent' => $draft['summary'] ?? $draft['title'] ?? 'Pending task draft',
        ]);
    }

    public function clearPendingTaskDraft(Project $project): void
    {
        $stateDocument = $project->state_document ?? $this->defaultStateDocument($project->description);

        unset($stateDocument['pending_task_draft']);
        $stateDocument['updated_at'] = now()->toISOString();

        $project->update([
            'state_document' => $stateDocument,
        ]);
    }

    protected function defaultStateDocument(?string $description = null): array
    {
        return [
            'summary' => $description ?? 'No project summary yet.',
            'current_working_intent' => null,
            'outstanding_tasks' => [],
            'log' => [],
            'updated_at' => now()->toISOString(),
        ];
    }

    protected function summarizeContext(array $context): array
    {
        $artifact = $context['artifact'] ?? [];

        if (! is_array($artifact)) {
            $artifact = [];
        }

        return [
            'task_type' => $context['task_type'] ?? 'general',
            'artifact_summary' => str((string) ($artifact['summary'] ?? ''))->squish()->limit(500)->value(),
            'sources' => collect($artifact['sources'] ?? [])->take(5)->values()->all(),
            'next_actions' => collect($artifact['next_actions'] ?? [])->take(5)->values()->all(),
            'task_count' => is_array($artifact['tasks'] ?? null) ? count($artifact['tasks']) : null,
        ];
    }
}
