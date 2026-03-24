<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HeartbeatLog;
use App\Models\Task;

class HeartbeatScheduler
{
    public function __construct(
        protected ProviderRegistry $providerRegistry,
        protected HeartbeatOrchestrator $orchestrator,
    ) {}

    public function execute(): HeartbeatLog
    {
        return $this->orchestrator->run('manual');
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
}
