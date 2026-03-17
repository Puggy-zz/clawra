<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Workflow;
use Illuminate\Database\Seeder;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        Workflow::query()->updateOrCreate(
            ['name' => 'Coordinator Intake Workflow'],
            [
                'description' => 'Minimal Phase 0 workflow for general coordinator requests.',
                'steps' => [
                    ['name' => 'Initialize', 'description' => 'Capture request context'],
                    ['name' => 'Research', 'description' => 'Collect supporting context when needed'],
                    ['name' => 'Finalize', 'description' => 'Prepare the next coordinator action'],
                ],
            ]
        );

        Workflow::query()->updateOrCreate(
            ['name' => 'Research Brief Workflow'],
            [
                'description' => 'Collect and summarize research findings.',
                'steps' => [
                    ['name' => 'Research', 'description' => 'Search for source material'],
                    ['name' => 'Analyze', 'description' => 'Extract key facts and risks'],
                    ['name' => 'Document', 'description' => 'Summarize the findings'],
                ],
            ]
        );

        Workflow::query()->updateOrCreate(
            ['name' => 'Planning Workflow'],
            [
                'description' => 'Build and store a structured project plan.',
                'steps' => [
                    ['name' => 'Initialize', 'description' => 'Capture goals and constraints'],
                    ['name' => 'Plan', 'description' => 'Create milestones and next actions'],
                    ['name' => 'Finalize', 'description' => 'Store the approved plan'],
                ],
            ]
        );
    }
}
