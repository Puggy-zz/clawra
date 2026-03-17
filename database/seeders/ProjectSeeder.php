<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        Project::query()->updateOrCreate(
            ['name' => 'Clawra Phase 0'],
            [
                'description' => 'Initial implementation of the Clawra system foundation.',
                'goals' => 'Complete provider awareness, coordinator orchestration, workflows, and heartbeat operations.',
                'status' => 'active',
                'state_document' => [
                    'summary' => 'Phase 0 foundation work is tracked here.',
                    'current_working_intent' => 'Complete the foundational orchestration loop.',
                    'outstanding_tasks' => [],
                    'log' => [],
                    'updated_at' => now()->toISOString(),
                ],
                'current_intent' => 'Implementing the Phase 0 roadmap',
            ]
        );
    }
}
