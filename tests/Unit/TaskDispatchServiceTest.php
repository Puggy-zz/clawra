<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\AgentRuntime;
use App\Models\Project;
use App\Models\Provider;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use App\Models\Task;
use App\Models\Workflow;
use App\Services\ProviderRegistry;
use App\Services\TaskDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('can be instantiated', function () {
    expect(new TaskDispatchService(new ProviderRegistry))->toBeInstanceOf(TaskDispatchService::class);
});

it('dispatches pending high and normal priority tasks', function () {
    $provider = Provider::factory()->create(['name' => 'synthetic', 'status' => 'active']);

    $route = ProviderRoute::factory()->create([
        'provider_id' => $provider->id,
        'harness' => 'laravel_ai',
        'capability_tags' => ['chat'],
        'usage_snapshot' => ['requests_remaining' => 10],
        'status' => 'active',
    ]);

    ProviderModel::factory()->create([
        'provider_route_id' => $route->id,
        'status' => 'active',
        'is_default' => true,
    ]);

    $project = Project::factory()->create();
    $workflow = Workflow::factory()->create();

    $normalTask = Task::factory()->create([
        'project_id' => $project->id,
        'workflow_id' => $workflow->id,
        'status' => 'pending',
        'priority' => 50,
        'name' => 'Normal task',
    ]);

    $lowTask = Task::factory()->create([
        'project_id' => $project->id,
        'workflow_id' => $workflow->id,
        'status' => 'pending',
        'priority' => 80,
        'name' => 'Low task',
    ]);

    $service = new TaskDispatchService(new ProviderRegistry);
    $dispatched = $service->dispatchEligibleTasks(false);

    expect($dispatched)->toHaveCount(1)
        ->and($normalTask->refresh()->status)->toBe('in-progress')
        ->and($lowTask->refresh()->status)->toBe('pending');
});

it('dispatches low priority tasks when includeLowPriority is true', function () {
    $provider = Provider::factory()->create(['name' => 'synthetic', 'status' => 'active']);

    $route = ProviderRoute::factory()->create([
        'provider_id' => $provider->id,
        'harness' => 'laravel_ai',
        'capability_tags' => ['chat'],
        'usage_snapshot' => ['requests_remaining' => 10],
        'status' => 'active',
    ]);

    ProviderModel::factory()->create([
        'provider_route_id' => $route->id,
        'status' => 'active',
        'is_default' => true,
    ]);

    $project = Project::factory()->create();
    $workflow = Workflow::factory()->create();

    $lowTask = Task::factory()->create([
        'project_id' => $project->id,
        'workflow_id' => $workflow->id,
        'status' => 'pending',
        'priority' => 80,
        'name' => 'Low priority task',
    ]);

    $service = new TaskDispatchService(new ProviderRegistry);
    $dispatched = $service->dispatchEligibleTasks(true);

    expect($dispatched)->toHaveCount(1)
        ->and($lowTask->refresh()->status)->toBe('in-progress');
});

it('detects high priority work in progress', function () {
    $project = Project::factory()->create();
    $workflow = Workflow::factory()->create();

    Task::factory()->create([
        'project_id' => $project->id,
        'workflow_id' => $workflow->id,
        'status' => 'in-progress',
        'priority' => 30,
    ]);

    $service = new TaskDispatchService(new ProviderRegistry);

    expect($service->hasHighPriorityWorkInProgress())->toBeTrue();
});

it('counts pending tasks by priority band', function () {
    $project = Project::factory()->create();
    $workflow = Workflow::factory()->create();

    Task::factory()->create(['project_id' => $project->id, 'workflow_id' => $workflow->id, 'status' => 'pending', 'priority' => 10]);
    Task::factory()->create(['project_id' => $project->id, 'workflow_id' => $workflow->id, 'status' => 'pending', 'priority' => 50]);
    Task::factory()->create(['project_id' => $project->id, 'workflow_id' => $workflow->id, 'status' => 'pending', 'priority' => 80]);

    $service = new TaskDispatchService(new ProviderRegistry);
    $counts = $service->countPendingByPriority();

    expect($counts['high'])->toBe(1)
        ->and($counts['normal'])->toBe(1)
        ->and($counts['low'])->toBe(1);
});

it('infers capability from task name keywords', function () {
    $project = Project::factory()->create();
    $workflow = Workflow::factory()->create();
    $service = new TaskDispatchService(new ProviderRegistry);

    $research = Task::factory()->make(['name' => 'Research competitors', 'description' => null]);
    $plan = Task::factory()->make(['name' => 'Plan the roadmap', 'description' => null]);
    $chat = Task::factory()->make(['name' => 'Write a summary', 'description' => null]);

    expect($service->inferCapability($research))->toBe('web-search')
        ->and($service->inferCapability($plan))->toBe('planning')
        ->and($service->inferCapability($chat))->toBe('chat');
});

it('dispatches tasks with an opencode agent runtime even when no laravel_ai route exists', function () {
    $provider = Provider::factory()->create(['name' => 'opencode-provider', 'status' => 'active']);

    $route = ProviderRoute::factory()->create([
        'provider_id' => $provider->id,
        'harness' => 'opencode',
        'capability_tags' => ['coding'],
        'status' => 'active',
    ]);

    $agent = Agent::factory()->create(['name' => 'Coder']);

    AgentRuntime::factory()->create([
        'agent_id' => $agent->id,
        'provider_route_id' => $route->id,
        'harness' => 'opencode',
        'is_default' => true,
        'status' => 'active',
    ]);

    $project = Project::factory()->create();
    $workflow = Workflow::factory()->create();

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'workflow_id' => $workflow->id,
        'recommended_agent_id' => $agent->id,
        'status' => 'pending',
        'priority' => 50,
        'name' => 'Build the landing page',
    ]);

    $service = new TaskDispatchService(new ProviderRegistry);
    $dispatched = $service->dispatchEligibleTasks(false);

    expect($dispatched)->toHaveCount(1)
        ->and($dispatched[0]['task_id'])->toBe($task->id)
        ->and($dispatched[0]['harness'])->toBe('opencode')
        ->and($task->refresh()->status)->toBe('in-progress');
});

it('does not dispatch tasks when agent has no active runtime and no laravel_ai route exists', function () {
    $agent = Agent::factory()->create(['name' => 'Unconfigured']);

    $project = Project::factory()->create();
    $workflow = Workflow::factory()->create();

    Task::factory()->create([
        'project_id' => $project->id,
        'workflow_id' => $workflow->id,
        'recommended_agent_id' => $agent->id,
        'status' => 'pending',
        'priority' => 50,
        'name' => 'Orphaned task',
    ]);

    $service = new TaskDispatchService(new ProviderRegistry);
    $dispatched = $service->dispatchEligibleTasks(false);

    expect($dispatched)->toBeEmpty();
});
