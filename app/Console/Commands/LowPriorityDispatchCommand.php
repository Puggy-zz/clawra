<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\HeartbeatOrchestrator;
use Illuminate\Console\Command;

class LowPriorityDispatchCommand extends Command
{
    protected $signature = 'clawra:dispatch-low';

    protected $description = 'Overnight low-priority task dispatch and project suggestion generation';

    public function handle(HeartbeatOrchestrator $orchestrator): int
    {
        $this->info('Running low-priority dispatch sweep...');

        try {
            $log = $orchestrator->runLowPriorityDispatch();
            $this->info(sprintf('Sweep complete. %d task(s) dispatched.', count($log->tasks_queued)));
        } catch (\Exception $e) {
            $this->error('Low-priority dispatch failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
