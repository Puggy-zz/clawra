<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AgentRuntime;
use App\Models\ProviderRoute;
use App\Models\Task;

class TaskDispatchService
{
    public function __construct(protected ProviderRegistry $providerRegistry) {}

    public function dispatchEligibleTasks(bool $includeLowPriority = false): array
    {
        $query = Task::query()
            ->with(['project', 'workflow', 'currentSubtask', 'recommendedAgent.runtimes'])
            ->where('status', 'pending');

        if (! $includeLowPriority) {
            $query->where('priority', '<', 75);
        }

        $tasks = $query->orderBy('priority')->orderBy('created_at')->get();

        $dispatched = [];

        foreach ($tasks as $task) {
            $capability = $this->inferCapability($task);
            $route = $this->resolveDispatchRoute($task, $capability);

            if ($route === null) {
                continue;
            }

            $task->update(['status' => 'in-progress']);

            $dispatched[] = [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'project' => $task->project?->name,
                'provider' => $route->provider->name,
                'route' => $route->name,
                'harness' => $route->harness,
                'capability' => $capability,
                'priority' => $task->priority,
                'queued_at' => now()->toISOString(),
            ];
        }

        return $dispatched;
    }

    /**
     * Resolve a dispatchable route for a task.
     *
     * If the task has a recommended agent with an active runtime, return a representative
     * route for that runtime so the task is eligible to dispatch (the actual execution
     * routing is handled inside ExecuteTaskJob via RuntimeExecutionService).
     *
     * Falls back to the best laravel_ai route for tasks without an assigned agent.
     */
    protected function resolveDispatchRoute(Task $task, string $capability): ?ProviderRoute
    {
        if ($task->recommendedAgent !== null) {
            $runtime = $task->recommendedAgent->runtimes
                ->where('status', 'active')
                ->sortByDesc('is_default')
                ->first();

            if ($runtime instanceof AgentRuntime) {
                return $runtime->route ?? $this->providerRegistry->getBestRouteForCapability($capability);
            }
        }

        return $this->providerRegistry->getBestRouteForCapability($capability, harness: 'laravel_ai');
    }

    public function hasHighPriorityWorkInProgress(): bool
    {
        return Task::query()
            ->where('status', 'in-progress')
            ->where('priority', '<', 75)
            ->exists();
    }

    public function countPendingByPriority(): array
    {
        $counts = Task::query()
            ->where('status', 'pending')
            ->selectRaw('
                SUM(CASE WHEN priority < 25 THEN 1 ELSE 0 END) as high,
                SUM(CASE WHEN priority >= 25 AND priority < 75 THEN 1 ELSE 0 END) as normal,
                SUM(CASE WHEN priority >= 75 THEN 1 ELSE 0 END) as low
            ')
            ->first();

        return [
            'high' => (int) ($counts?->high ?? 0),
            'normal' => (int) ($counts?->normal ?? 0),
            'low' => (int) ($counts?->low ?? 0),
        ];
    }

    public function inferCapability(Task $task): string
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
