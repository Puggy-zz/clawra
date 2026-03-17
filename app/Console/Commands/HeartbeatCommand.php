<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\HeartbeatScheduler;
use Illuminate\Console\Command;

class HeartbeatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clawra:heartbeat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute the Clawra heartbeat scheduler';

    /**
     * Execute the console command.
     */
    public function handle(HeartbeatScheduler $scheduler): int
    {
        $this->info('Starting Clawra heartbeat...');

        try {
            $scheduler->execute();
            $this->info('Heartbeat completed successfully.');
        } catch (\Exception $e) {
            $this->error('Heartbeat failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
