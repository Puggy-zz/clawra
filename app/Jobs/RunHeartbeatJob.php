<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\HeartbeatOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunHeartbeatJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(public readonly string $trigger = 'manual') {}

    public function handle(HeartbeatOrchestrator $orchestrator): void
    {
        $orchestrator->run($this->trigger);
    }
}
