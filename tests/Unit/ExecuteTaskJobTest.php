<?php

declare(strict_types=1);

use App\Agents\ReviewerAgent;
use App\Jobs\ExecuteTaskJob;
use App\Models\Agent;
use App\Models\AgentRuntime;
use App\Models\ProcessLog;
use App\Models\Project;
use App\Models\Provider;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use App\Models\Task;
use App\Models\Workflow;
use App\Services\ProcessLogService;
use App\Services\RuntimeExecutionService;
use App\Services\SandboxManagerService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function makeTaskWithRuntime(string $agentName = 'Planner', string $harness = 'laravel_ai'): array
{
    $provider = Provider::factory()->create(['name' => 'synthetic', 'status' => 'active']);
    $route = ProviderRoute::factory()->create([
        'provider_id' => $provider->id,
        'harness' => $harness,
        'capability_tags' => ['chat', 'planning'],
        'usage_snapshot' => ['requests_remaining' => 10],
        'status' => 'active',
    ]);
    $model = ProviderModel::factory()->create([
        'provider_route_id' => $route->id,
        'status' => 'active',
        'is_default' => true,
        'external_name' => 'synthetic-model',
    ]);
    $agent = Agent::factory()->create(['name' => $agentName]);
    AgentRuntime::factory()->create([
        'agent_id' => $agent->id,
        'provider_route_id' => $route->id,
        'provider_model_id' => $model->id,
        'harness' => $harness,
        'runtime_type' => 'laravel_ai',
        'name' => strtolower($agentName).'-runtime',
        'is_default' => true,
    ]);
    $project = Project::factory()->create(['name' => 'Test Project']);
    $workflow = Workflow::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'workflow_id' => $workflow->id,
        'recommended_agent_id' => $agent->id,
        'name' => 'Test Task',
        'description' => 'Do something useful.',
        'status' => 'in-progress',
        'priority' => 50,
    ]);

    return compact('task', 'agent', 'project');
}

function noopReviewer(): ReviewerAgent
{
    $reviewer = Mockery::mock(ReviewerAgent::class);
    $reviewer->shouldNotReceive('reviewTaskCompletion');

    return $reviewer;
}

function noopSandboxManager(): SandboxManagerService
{
    $manager = Mockery::mock(SandboxManagerService::class);
    $manager->allows('remove');

    return $manager;
}

it('can be instantiated with a task id', function () {
    $job = new ExecuteTaskJob(42);

    expect($job->taskId)->toBe(42)
        ->and($job->timeout)->toBe(3600)
        ->and($job->tries)->toBe(1);
});

it('bails silently when task is not found', function () {
    $executor = Mockery::mock(RuntimeExecutionService::class);
    $executor->shouldNotReceive('executeAgent');

    $job = new ExecuteTaskJob(99999);
    $job->handle($executor, app(ProcessLogService::class), noopReviewer(), noopSandboxManager());
});

it('bails silently when task is not in-progress', function () {
    $project = Project::factory()->create();
    $workflow = Workflow::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'workflow_id' => $workflow->id,
        'status' => 'pending',
    ]);

    $executor = Mockery::mock(RuntimeExecutionService::class);
    $executor->shouldNotReceive('executeAgent');

    $job = new ExecuteTaskJob($task->id);
    $job->handle($executor, app(ProcessLogService::class), noopReviewer(), noopSandboxManager());

    expect($task->refresh()->status)->toBe('pending');
});

it('marks task completed on successful laravel_ai execution without reviewer', function () {
    ['task' => $task, 'agent' => $agent] = makeTaskWithRuntime();

    $executor = Mockery::mock(RuntimeExecutionService::class);
    $executor->shouldReceive('executeAgent')
        ->once()
        ->with($agent->name, Mockery::type('string'), null, Mockery::any())
        ->andReturn(['success' => true, 'text' => 'Done.', 'status' => 'completed', 'harness' => 'laravel_ai']);

    $job = new ExecuteTaskJob($task->id);
    $job->handle($executor, app(ProcessLogService::class), noopReviewer(), noopSandboxManager());

    $task->refresh();
    expect($task->status)->toBe('completed')
        ->and($task->result)->toBe('Done.');
    expect(ProcessLog::query()->where('task_id', $task->id)->exists())->toBeTrue();
});

it('marks task completed after opencode run when reviewer approves', function () {
    ['task' => $task, 'agent' => $agent] = makeTaskWithRuntime('Developer', 'opencode');

    $executor = Mockery::mock(RuntimeExecutionService::class);
    $executor->shouldReceive('executeAgent')
        ->once()
        ->andReturn(['success' => true, 'text' => 'All tests pass.', 'status' => 'completed', 'harness' => 'opencode']);

    $reviewer = Mockery::mock(ReviewerAgent::class);
    $reviewer->shouldReceive('reviewTaskCompletion')
        ->once()
        ->with($task->name, $task->description, 'All tests pass.')
        ->andReturn(['decision' => 'completed', 'reasoning' => 'Task done.']);

    $job = new ExecuteTaskJob($task->id);
    $job->handle($executor, app(ProcessLogService::class), $reviewer, noopSandboxManager());

    $task->refresh();
    expect($task->status)->toBe('completed');
    $log = ProcessLog::query()->where('task_id', $task->id)->latest()->first();
    expect($log?->context['review']['decision'])->toBe('completed');
});

it('marks task failed after opencode run when reviewer says incomplete', function () {
    ['task' => $task] = makeTaskWithRuntime('Developer', 'opencode');

    $executor = Mockery::mock(RuntimeExecutionService::class);
    $executor->shouldReceive('executeAgent')
        ->once()
        ->andReturn(['success' => true, 'text' => 'Partial work done.', 'status' => 'completed', 'harness' => 'opencode']);

    $reviewer = Mockery::mock(ReviewerAgent::class);
    $reviewer->shouldReceive('reviewTaskCompletion')
        ->once()
        ->andReturn(['decision' => 'incomplete', 'reasoning' => 'Missing implementation.']);

    $job = new ExecuteTaskJob($task->id);
    $job->handle($executor, app(ProcessLogService::class), $reviewer, noopSandboxManager());

    $task->refresh();
    expect($task->status)->toBe('failed');
    $log = ProcessLog::query()->where('task_id', $task->id)->latest()->first();
    expect($log?->context['review']['decision'])->toBe('incomplete');
});

it('marks task failed after opencode run when reviewer says failed', function () {
    ['task' => $task] = makeTaskWithRuntime('Developer', 'opencode');

    $executor = Mockery::mock(RuntimeExecutionService::class);
    $executor->shouldReceive('executeAgent')
        ->once()
        ->andReturn(['success' => true, 'text' => 'Errors encountered.', 'status' => 'completed', 'harness' => 'opencode']);

    $reviewer = Mockery::mock(ReviewerAgent::class);
    $reviewer->shouldReceive('reviewTaskCompletion')
        ->once()
        ->andReturn(['decision' => 'failed', 'reasoning' => 'Build broke.']);

    $job = new ExecuteTaskJob($task->id);
    $job->handle($executor, app(ProcessLogService::class), $reviewer, noopSandboxManager());

    $task->refresh();
    expect($task->status)->toBe('failed');
});

it('marks task failed on unsuccessful execution', function () {
    ['task' => $task] = makeTaskWithRuntime();

    $executor = Mockery::mock(RuntimeExecutionService::class);
    $executor->shouldReceive('executeAgent')
        ->once()
        ->andReturn(['success' => false, 'text' => '', 'status' => 'failed', 'error' => 'Provider unreachable', 'harness' => 'laravel_ai']);

    $job = new ExecuteTaskJob($task->id);
    $job->handle($executor, app(ProcessLogService::class), noopReviewer(), noopSandboxManager());

    $task->refresh();
    expect($task->status)->toBe('failed')
        ->and($task->result)->toBe('');
    $log = ProcessLog::query()->where('task_id', $task->id)->latest()->first();
    expect($log)->not->toBeNull()
        ->and($log->status)->toBe('failed');
});

it('marks task failed and logs on thrown exception', function () {
    ['task' => $task] = makeTaskWithRuntime();

    $executor = Mockery::mock(RuntimeExecutionService::class);
    $executor->shouldReceive('executeAgent')
        ->once()
        ->andThrow(new RuntimeException('Connection timeout'));

    $job = new ExecuteTaskJob($task->id);
    $job->handle($executor, app(ProcessLogService::class), noopReviewer(), noopSandboxManager());

    $task->refresh();
    expect($task->status)->toBe('failed')
        ->and($task->result)->toBe('Connection timeout');
    $log = ProcessLog::query()->where('task_id', $task->id)->latest()->first();
    expect($log?->context['exception'])->toBe('Connection timeout');
});

it('infers Researcher agent from task name containing research keyword', function () {
    $project = Project::factory()->create();
    $workflow = Workflow::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'workflow_id' => $workflow->id,
        'name' => 'Research competitor pricing',
        'description' => null,
        'status' => 'in-progress',
        'recommended_agent_id' => null,
    ]);

    $executor = Mockery::mock(RuntimeExecutionService::class);
    $executor->shouldReceive('executeAgent')
        ->once()
        ->with('Researcher', Mockery::type('string'), null, Mockery::any())
        ->andReturn(['success' => true, 'text' => 'Done.', 'status' => 'completed', 'harness' => 'laravel_ai']);

    $job = new ExecuteTaskJob($task->id);
    $job->handle($executor, app(ProcessLogService::class), noopReviewer(), noopSandboxManager());
});

it('infers Planner agent from task name containing plan keyword', function () {
    $project = Project::factory()->create();
    $workflow = Workflow::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'workflow_id' => $workflow->id,
        'name' => 'Plan the sprint',
        'description' => null,
        'status' => 'in-progress',
        'recommended_agent_id' => null,
    ]);

    $executor = Mockery::mock(RuntimeExecutionService::class);
    $executor->shouldReceive('executeAgent')
        ->once()
        ->with('Planner', Mockery::type('string'), null, Mockery::any())
        ->andReturn(['success' => true, 'text' => 'Done.', 'status' => 'completed', 'harness' => 'laravel_ai']);

    $job = new ExecuteTaskJob($task->id);
    $job->handle($executor, app(ProcessLogService::class), noopReviewer(), noopSandboxManager());
});

it('defaults to Planner agent when no keyword matches', function () {
    $project = Project::factory()->create();
    $workflow = Workflow::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'workflow_id' => $workflow->id,
        'name' => 'Write release notes',
        'description' => null,
        'status' => 'in-progress',
        'recommended_agent_id' => null,
    ]);

    $executor = Mockery::mock(RuntimeExecutionService::class);
    $executor->shouldReceive('executeAgent')
        ->once()
        ->with('Planner', Mockery::type('string'), null, Mockery::any())
        ->andReturn(['success' => true, 'text' => 'Done.', 'status' => 'completed', 'harness' => 'laravel_ai']);

    $job = new ExecuteTaskJob($task->id);
    $job->handle($executor, app(ProcessLogService::class), noopReviewer(), noopSandboxManager());
});
