<?php

declare(strict_types=1);

use App\Agents\CoordinatorAgent;
use App\Ai\Agents\ClawraCoordinatorConversationAgent;
use App\Models\ProcessLog;
use App\Services\ProjectConversationService;
use App\Services\ProjectService;
use App\Services\RuntimeExecutionService;
use Database\Seeders\AgentSeeder;
use Database\Seeders\ProviderSeeder;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Test that the coordinator agent can be instantiated
it('can be instantiated', function () {
    $agent = new CoordinatorAgent(createMockAiService());
    expect($agent)->toBeInstanceOf(CoordinatorAgent::class);
});

// Test that the coordinator agent can process messages
it('can process messages', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);
    ClawraCoordinatorConversationAgent::fake([
        ['action' => 'chat', 'response' => 'Hello from Clawra.', 'task_name' => null],
    ]);

    $agent = new CoordinatorAgent(createMockAiService());

    $result = $agent->processMessage('Test message');
    expect($result)->toBe('Hello from Clawra.');
});

// Test that the coordinator agent can decompose requests
it('can decompose requests', function () {
    $agent = new CoordinatorAgent(createMockAiService());

    $result = $agent->decomposeRequest('Test request');
    expect($result)->toBeArray();
});

// Test that the coordinator agent can route tasks
it('can route tasks', function () {
    $this->seed(AgentSeeder::class);

    $agent = new CoordinatorAgent(createMockAiService());

    $task = ['type' => 'planning'];
    $result = $agent->routeTask($task);
    expect($result)->toBeArray();
    expect($result['agent'])->toBe('Planner');
});

it('creates tasks immediately when the user describes work to do', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);
    ClawraCoordinatorConversationAgent::fake([
        [
            'action' => 'create_tasks',
            'response' => 'Created 2 research tasks.',
            'tasks' => [
                ['name' => 'Research API rate limits', 'description' => 'Look up rate limits.', 'workflow_type' => 'research', 'recommended_agent' => 'Researcher'],
                ['name' => 'Research auth patterns', 'description' => 'Review auth options.', 'workflow_type' => 'research', 'recommended_agent' => 'Researcher'],
            ],
        ],
    ]);

    $agent = new CoordinatorAgent(createMockAiService());

    $result = $agent->orchestrateRequest('Research API rate limits and auth patterns.');

    expect($result['created_task'])->toBeTrue()
        ->and($result['task']->name)->toBe('Research API rate limits');

    expect(ProcessLog::query()->where('kind', 'task.created')->count())->toBe(2);
});

it('persists all created tasks in the database', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);

    $projectService = app(ProjectService::class);
    $conversationService = app(ProjectConversationService::class);
    $project = $projectService->ensureInboxProject();
    $conversation = $conversationService->ensureDefaultConversation($project);

    ClawraCoordinatorConversationAgent::fake([
        [
            'action' => 'create_tasks',
            'response' => 'Created the task.',
            'tasks' => [
                ['name' => 'Research the API', 'description' => 'Look up rate limits.', 'workflow_type' => 'research', 'recommended_agent' => 'Researcher'],
            ],
        ],
    ]);

    $agent = new CoordinatorAgent(createMockAiService());
    $result = $agent->orchestrateRequest('Research API rate limits.', $project->id, $conversation->id);

    expect($result['task']->name)->toBe('Research the API')
        ->and(\App\Models\Task::query()->where('project_conversation_id', $conversation->id)->count())->toBe(1);
});

it('keeps conversational responses task-free when the intent agent says chat', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);
    ClawraCoordinatorConversationAgent::fake([
        ['action' => 'chat', 'response' => 'Hello from Clawra.', 'task_name' => null],
    ]);

    $agent = new CoordinatorAgent(createMockAiService());

    $result = $agent->orchestrateRequest('hi');

    expect($result['created_task'])->toBeFalse()
        ->and($result['task_type'])->toBe('chat')
        ->and($result['response'])->toBe('Hello from Clawra.');
});

it('keeps task names concise when the agent returns long names', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);
    ClawraCoordinatorConversationAgent::fake([
        [
            'action' => 'create_tasks',
            'response' => 'Task created.',
            'tasks' => [
                ['name' => str_repeat('A', 200), 'description' => 'Long name test.', 'workflow_type' => 'general', 'recommended_agent' => 'Planner'],
            ],
        ],
    ]);

    $agent = new CoordinatorAgent(createMockAiService());
    $result = $agent->orchestrateRequest('Do something.');

    expect(strlen((string) $result['task']->name))->toBeLessThanOrEqual(123);
});

it('updates an existing task when the agent returns update_task', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);

    $projectService = app(ProjectService::class);
    $conversationService = app(ProjectConversationService::class);
    $project = $projectService->ensureInboxProject();
    $conversation = $conversationService->ensureDefaultConversation($project);

    $workflowService = app(\App\Services\WorkflowService::class);
    $taskService = app(\App\Services\TaskService::class);
    $workflow = $workflowService->getDefaultWorkflowForType('general');
    $task = $taskService->createTaskWithWorkflow($project->id, $workflow->id, 'Original name', 'Original desc', null, $conversation->id);

    ClawraCoordinatorConversationAgent::fake([
        [
            'action' => 'update_task',
            'response' => 'Updated the task name.',
            'task_id' => $task->id,
            'task_name' => 'Updated name',
            'task_description' => 'Updated desc.',
            'task_workflow_type' => 'general',
            'task_recommended_agent' => 'Planner',
        ],
    ]);

    $agent = new CoordinatorAgent(createMockAiService());
    $result = $agent->orchestrateRequest('Change the task name to Updated name.', $project->id, $conversation->id);

    expect($result['task']->name)->toBe('Updated name')
        ->and(ProcessLog::query()->where('kind', 'task.updated')->exists())->toBeTrue();
});

it('uses runtime execution for development tasks when available', function () {
    $runtimeExecutionService = Mockery::mock(RuntimeExecutionService::class);
    $runtimeExecutionService->shouldReceive('executeAgent')->once()->with('Developer', 'Build the thing')->andReturn([
        'success' => true,
        'text' => 'OpenCode handled it.',
        'status' => 'completed',
        'harness' => 'opencode',
        'runtime' => 'builder',
    ]);

    $agent = new CoordinatorAgent(
        createMockAiService(),
        runtimeExecutionService: $runtimeExecutionService,
    );

    $result = $agent->routeTask([
        'type' => 'development',
        'description' => 'Build the thing',
    ]);

    expect($result['agent'])->toBe('Developer')
        ->and($result['result']['message'])->toBe('OpenCode handled it.')
        ->and($result['result']['harness'])->toBe('opencode');
});
