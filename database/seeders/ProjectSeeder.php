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
            ['name' => 'Inbox'],
            [
                'description' => 'Holding area for tasks and ideas not yet assigned to a specific project.',
                'goals' => null,
                'status' => 'active',
                'workspace_path' => 'D:/Projects/Clawra/',
                'state_document' => [],
                'current_intent' => null,
            ]
        );

        Project::query()->updateOrCreate(
            ['name' => 'Clawra'],
            [
                'description' => 'Personal AI orchestration system — Laravel + NativePHP desktop app for Windows.',
                'goals' => 'Build a coordinator-driven agent orchestration platform leveraging existing inference subscriptions without additional per-token costs.',
                'status' => 'active',
                'workspace_path' => 'D:/Projects/Clawra/',
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
