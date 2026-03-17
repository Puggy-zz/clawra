<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HeartbeatLog;
use App\Models\Project;
use App\Models\Task;

class HeartbeatScheduler
{
    public function __construct(protected ProviderRegistry $providerRegistry) {}

    public function execute(): HeartbeatLog
    {
        $heartbeatData = $this->collectHeartbeatData();

        return HeartbeatLog::query()->create([
            'timestamp' => now(),
            'decisions' => $heartbeatData['decisions'],
            'tasks_queued' => $heartbeatData['tasks_queued'],
            'provider_status' => $heartbeatData['provider_status'],
            'created_at' => now(),
        ]);
    }

    public function checkSystemHealth(): array
    {
        return [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'provider_status' => $this->providerRegistry->providerStatusSnapshot(),
            'task_counts' => [
                'pending' => Task::query()->where('status', 'pending')->count(),
                'in_progress' => Task::query()->where('status', 'in-progress')->count(),
                'completed' => Task::query()->where('status', 'completed')->count(),
            ],
        ];
    }

    protected function collectHeartbeatData(): array
    {
        $providerStatus = $this->providerRegistry->providerStatusSnapshot();
        $queuedTasks = $this->queueEligibleTasks();
        $decisions = array_merge(
            $this->collectProjectDecisions(),
            [[
                'type' => 'provider-sync',
                'message' => sprintf('Synchronized %d providers and queued %d tasks.', count($providerStatus), count($queuedTasks)),
                'timestamp' => now()->toISOString(),
            ]],
        );

        return [
            'decisions' => $decisions,
            'tasks_queued' => $queuedTasks,
            'provider_status' => $providerStatus,
        ];
    }

    protected function queueEligibleTasks(): array
    {
        $tasks = Task::query()
            ->with(['project', 'workflow', 'currentSubtask'])
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        $queued = [];

        foreach ($tasks as $task) {
            $capability = $this->inferCapability($task);
            $route = $this->providerRegistry->getBestRouteForCapability($capability, harness: 'laravel_ai');

            if ($route === null) {
                continue;
            }

            $task->update(['status' => 'in-progress']);

            $queued[] = [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'project' => $task->project?->name,
                'provider' => $route->provider->name,
                'route' => $route->name,
                'harness' => $route->harness,
                'capability' => $capability,
                'queued_at' => now()->toISOString(),
            ];
        }

        return $queued;
    }

    protected function collectProjectDecisions(): array
    {
        return Project::query()
            ->withCount(['tasks'])
            ->orderBy('name')
            ->get()
            ->map(function (Project $project): array {
                return [
                    'type' => 'project-review',
                    'project_id' => $project->id,
                    'project' => $project->name,
                    'message' => sprintf('Project %s has %d tracked tasks and current intent "%s".', $project->name, $project->tasks_count, $project->current_intent ?? 'none'),
                    'timestamp' => now()->toISOString(),
                ];
            })
            ->all();
    }

    protected function inferCapability(Task $task): string
    {
        $text = strtolower(implode(' ', array_filter([
            $task->name,
            $task->description,
            $task->currentSubtask?->name,
        ])));

        if (str_contains($text, 'research') || str_contains($text, 'investigate')) {
            return 'web-search';
        }

        if (str_contains($text, 'plan') || str_contains($text, 'initialize') || str_contains($text, 'finalize')) {
            return 'planning';
        }

        return 'chat';
    }
}
