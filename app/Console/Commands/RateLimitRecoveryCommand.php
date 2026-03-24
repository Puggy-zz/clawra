<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\HeartbeatOrchestrator;
use Illuminate\Console\Command;

class RateLimitRecoveryCommand extends Command
{
    protected $signature = 'clawra:rl-recovery';

    protected $description = 'Check for expired rate-limit windows and restore recovered routes';

    public function handle(HeartbeatOrchestrator $orchestrator): int
    {
        $this->info('Running rate-limit recovery check...');

        try {
            $log = $orchestrator->runRecoveryOnly();
            $this->info(sprintf('Recovery complete. %d task(s) dispatched.', count($log->tasks_queued)));
        } catch (\Exception $e) {
            $this->error('Recovery failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
