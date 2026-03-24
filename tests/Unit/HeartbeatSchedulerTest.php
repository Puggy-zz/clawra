<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\HeartbeatLog;
use App\Models\Project;
use App\Models\Provider;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use App\Models\Task;
use App\Models\Workflow;
use App\Services\HeartbeatScheduler;
use App\Services\QuotaSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('can be instantiated', function () {
    expect(app(HeartbeatScheduler::class))->toBeInstanceOf(HeartbeatScheduler::class);
});

it('queues eligible pending tasks and logs the heartbeat', function () {
    $this->mock(QuotaSyncService::class)->shouldReceive('syncAll')->andReturn([]);

    Agent::factory()->create(['name' => 'Planner']);

    Provider::factory()->create([
        'name' => 'synthetic',
        'capability_tags' => ['planning', 'chat'],
        'priority_preferences' => ['planning' => 1, 'default' => 5],
        'usage_snapshot' => ['requests_remaining' => 10, 'reset_at' => now()->addHours(5)->toISOString()],
        'status' => 'active',
    ]);

    $route = ProviderRoute::factory()->create([
        'provider_id' => Provider::query()->where('name', 'synthetic')->value('id'),
        'name' => 'synthetic-chat',
        'harness' => 'laravel_ai',
        'capability_tags' => ['planning', 'chat'],
        'usage_snapshot' => ['requests_remaining' => 10, 'reset_at' => now()->addHours(5)->toISOString()],
        'status' => 'active',
    ]);

    ProviderModel::factory()->create([
        'provider_route_id' => $route->id,
        'name' => 'deepseek-v3',
        'external_name' => 'deepseek-v3',
        'capabilities' => ['planning', 'chat'],
        'is_default' => true,
        'status' => 'active',
    ]);

    $project = Project::factory()->create(['current_intent' => 'Plan the next milestone']);
    $workflow = Workflow::factory()->create([
        'name' => 'Planning Workflow',
        'steps' => [
            ['name' => 'Plan', 'description' => 'Create a plan'],
        ],
    ]);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'workflow_id' => $workflow->id,
        'name' => 'Plan release workflow',
        'status' => 'pending',
    ]);

    $log = app(HeartbeatScheduler::class)->execute();

    expect($log)->toBeInstanceOf(HeartbeatLog::class)
        ->and(HeartbeatLog::query()->count())->toBe(1)
        ->and($task->refresh()->status)->toBe('in-progress')
        ->and($log->tasks_queued)->toHaveCount(1)
        ->and($log->provider_status)->toHaveCount(1)
        ->and($log->trigger)->toBe('manual')
        ->and($log->run_type)->toBe('full');
});
