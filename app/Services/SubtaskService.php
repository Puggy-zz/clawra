<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agent;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class SubtaskService
{
    /**
     * @var array<string, array{role: string, description: string, model: string, fallback_model: string, tools: array<int, string>}>
     */
    protected array $defaultAgents = [
        'Planner' => [
            'role' => 'Task Planning Specialist',
            'description' => 'Builds project plans and task breakdowns.',
            'model' => 'synthetic',
            'fallback_model' => 'gemini',
            'tools' => ['task_planning', 'project_breakdown'],
        ],
        'Researcher' => [
            'role' => 'Research Specialist',
            'description' => 'Conducts web research through synthetic search.',
            'model' => 'synthetic',
            'fallback_model' => 'gemini',
            'tools' => ['web_search', 'summarization'],
        ],
        'Developer' => [
            'role' => 'Code Implementation Specialist',
            'description' => 'Reserved for Phase 1 sandbox execution.',
            'model' => 'synthetic',
            'fallback_model' => 'gemini',
            'tools' => ['code_editor', 'terminal'],
        ],
        'Reviewer' => [
            'role' => 'Quality Assurance Specialist',
            'description' => 'Reserved for review workflows.',
            'model' => 'synthetic',
            'fallback_model' => 'gemini',
            'tools' => ['review'],
        ],
        'Test Writer' => [
            'role' => 'Test Authoring Specialist',
            'description' => 'Reserved for TDD workflows in Phase 1.',
            'model' => 'synthetic',
            'fallback_model' => 'gemini',
            'tools' => ['testing'],
        ],
    ];

    public function getAllSubtasks(): Collection
    {
        return Subtask::all();
    }

    public function getSubtaskById(int $id): ?Subtask
    {
        return Subtask::find($id);
    }

    public function createSubtask(array $data): Subtask
    {
        return Subtask::create($data);
    }

    public function updateSubtask(int $id, array $data): bool
    {
        $subtask = $this->getSubtaskById($id);

        return $subtask instanceof Subtask ? $subtask->update($data) : false;
    }

    public function deleteSubtask(int $id): bool
    {
        $subtask = $this->getSubtaskById($id);

        return $subtask instanceof Subtask ? (bool) $subtask->delete() : false;
    }

    public function createSubtasksForTask(int $taskId, array $steps, ?int $preferredAgentId = null): array
    {
        $task = Task::find($taskId);

        if (! $task instanceof Task) {
            throw new \InvalidArgumentException('Invalid task ID');
        }

        $subtasks = [];

        foreach ($steps as $index => $step) {
            $stepName = $step['name'] ?? 'Coordinate';

            $subtasks[] = Subtask::query()->create([
                'task_id' => $taskId,
                'agent_id' => $index === 0 && $preferredAgentId !== null ? $preferredAgentId : $this->getAgentForStep($stepName),
                'name' => $stepName,
                'inputs' => $step['inputs'] ?? [],
                'outputs' => [],
                'status' => 'pending',
                'order' => $index + 1,
            ]);
        }

        return $subtasks;
    }

    private function getAgentForStep(string $stepName): int
    {
        $normalized = Str::lower($stepName);

        $agentName = match (true) {
            str_contains($normalized, 'research'), str_contains($normalized, 'analy'), str_contains($normalized, 'document') => 'Researcher',
            str_contains($normalized, 'review') => 'Reviewer',
            str_contains($normalized, 'implement'), str_contains($normalized, 'execute') => 'Developer',
            str_contains($normalized, 'test') => 'Test Writer',
            default => 'Planner',
        };

        $agent = Agent::query()->where('name', $agentName)->first();

        if ($agent instanceof Agent) {
            return $agent->id;
        }

        $attributes = $this->defaultAgents[$agentName] ?? $this->defaultAgents['Planner'];

        $agent = Agent::query()->firstOrCreate(
            ['name' => $agentName],
            $attributes,
        );

        return $agent->id;
    }
}
