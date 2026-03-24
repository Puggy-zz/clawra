<?php

declare(strict_types=1);

namespace App\Services;

use App\Agents\CoordinatorAgent;
use App\Jobs\ExecuteTaskJob;
use App\Models\HeartbeatLog;
use App\Models\Project;
use App\Models\Task;

class HeartbeatOrchestrator
{
    public function __construct(
        protected ProviderRegistry $providerRegistry,
        protected RateLimitRecoveryService $rateLimitRecovery,
        protected QuotaSyncService $quotaSync,
        protected TaskDispatchService $taskDispatch,
        protected CoordinatorAgent $coordinatorAgent,
    ) {}

    public function run(string $trigger = 'scheduled', bool $includeLowPriority = false): HeartbeatLog
    {
        $syncResults = $this->quotaSync->syncAll();
        $recovered = $this->rateLimitRecovery->recoverExpiredWindows();
        $dispatched = $this->taskDispatch->dispatchEligibleTasks($includeLowPriority);

        foreach ($dispatched as $record) {
            ExecuteTaskJob::dispatch($record['task_id']);
        }

        $providerStatus = $this->providerRegistry->providerStatusSnapshot();

        $decisions = array_filter([
            count($recovered) > 0 ? [
                'type' => 'rate-limit-recovery',
                'message' => sprintf('Recovered %d rate-limited route(s).', count($recovered)),
                'routes' => $recovered,
                'timestamp' => now()->toISOString(),
            ] : null,
            count($syncResults) > 0 ? [
                'type' => 'quota-sync',
                'message' => sprintf('Synced quotas for %d route(s).', count($syncResults)),
                'timestamp' => now()->toISOString(),
            ] : null,
            [
                'type' => 'provider-sync',
                'message' => sprintf('Synchronized %d providers and queued %d tasks.', count($providerStatus), count($dispatched)),
                'timestamp' => now()->toISOString(),
            ],
        ]);

        return HeartbeatLog::query()->create([
            'timestamp' => now(),
            'trigger' => $trigger,
            'run_type' => 'full',
            'decisions' => array_values($decisions),
            'tasks_queued' => $dispatched,
            'provider_status' => $providerStatus,
            'created_at' => now(),
        ]);
    }

    public function runRecoveryOnly(string $trigger = 'rate-limit-recovery'): HeartbeatLog
    {
        $recovered = $this->rateLimitRecovery->recoverExpiredWindows();
        $dispatched = $this->taskDispatch->dispatchEligibleTasks(false);

        foreach ($dispatched as $record) {
            ExecuteTaskJob::dispatch($record['task_id']);
        }

        $providerStatus = $this->providerRegistry->providerStatusSnapshot();

        $decisions = [[
            'type' => 'rate-limit-recovery',
            'message' => sprintf('Recovery check: %d route(s) restored.', count($recovered)),
            'routes' => $recovered,
            'timestamp' => now()->toISOString(),
        ]];

        return HeartbeatLog::query()->create([
            'timestamp' => now(),
            'trigger' => $trigger,
            'run_type' => 'recovery-only',
            'decisions' => $decisions,
            'tasks_queued' => $dispatched,
            'provider_status' => $providerStatus,
            'created_at' => now(),
        ]);
    }

    public function runLowPriorityDispatch(): HeartbeatLog
    {
        $dispatched = [];
        $suggestions = [];
        $decisions = [];

        if ($this->taskDispatch->hasHighPriorityWorkInProgress()) {
            $decisions[] = [
                'type' => 'low-priority-sweep',
                'message' => 'Skipped low-priority dispatch: high/normal priority work is in progress.',
                'timestamp' => now()->toISOString(),
            ];
        } else {
            $dispatched = $this->taskDispatch->dispatchEligibleTasks(true);

            foreach ($dispatched as $record) {
                ExecuteTaskJob::dispatch($record['task_id']);
            }

            $suggestions = $this->generateProjectSuggestions();

            $decisions[] = [
                'type' => 'low-priority-sweep',
                'message' => sprintf('Dispatched %d low-priority task(s).', count($dispatched)),
                'timestamp' => now()->toISOString(),
            ];

            if (count($suggestions) > 0) {
                $decisions[] = [
                    'type' => 'suggestions',
                    'message' => sprintf('Generated %d project suggestion(s).', count($suggestions)),
                    'suggestions' => $suggestions,
                    'timestamp' => now()->toISOString(),
                ];
            }
        }

        $providerStatus = $this->providerRegistry->providerStatusSnapshot();

        return HeartbeatLog::query()->create([
            'timestamp' => now(),
            'trigger' => 'scheduled',
            'run_type' => 'dispatch-only',
            'decisions' => $decisions,
            'tasks_queued' => $dispatched,
            'provider_status' => $providerStatus,
            'created_at' => now(),
        ]);
    }

    public function generateProjectSuggestions(): array
    {
        $projects = Project::query()
            ->withCount(['tasks' => fn ($q) => $q->whereNotIn('status', ['completed', 'failed', 'draft'])])
            ->orderBy('name')
            ->get();

        $summaries = [];

        foreach ($projects as $project) {
            $prompt = sprintf(
                'You are reviewing project "%s". Current intent: "%s". Active task count: %d. Identify the single most valuable next action. Reply in one concise sentence.',
                $project->name,
                $project->current_intent ?? 'none',
                $project->tasks_count,
            );

            try {
                $suggestion = $this->coordinatorAgent->processMessage($prompt, $project->id);

                $task = Task::query()->create([
                    'project_id' => $project->id,
                    'workflow_id' => $project->tasks()->first()?->workflow_id ?? 1,
                    'name' => sprintf('Suggestion: %s', str($suggestion)->limit(80)->value()),
                    'description' => $suggestion,
                    'status' => 'draft',
                    'priority' => 50,
                ]);

                $summaries[] = [
                    'project' => $project->name,
                    'task_id' => $task->id,
                    'suggestion' => str($suggestion)->limit(120)->value(),
                ];
            } catch (\Throwable) {
                // Skip silently — suggestions are best-effort.
            }
        }

        return $summaries;
    }
}
