<?php

declare(strict_types=1);

use App\Agents\CoordinatorAgent;
use App\Ai\Agents\ClawraCoordinatorConversationAgent;
use App\Models\ProcessLog;
use App\Services\ProjectConversationService;
use App\Services\ProjectService;
use App\Services\RuntimeExecutionService;
use App\Services\TaskService;
use App\Services\WorkflowService;
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
        ['action' => 'chat', 'response' => 'Hello from Clawra.', 'draft' => null],
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

it('creates a draft instead of a task when the intent agent marks a request actionable', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);
    ClawraCoordinatorConversationAgent::fake([
        [
            'action' => 'draft',
            'response' => 'I drafted a planning task. Tell me to create it when you are ready.',
            'draft' => [
                'title' => 'Plan the next milestone',
                'summary' => 'Define the next project milestone and success conditions.',
                'description' => 'Create a planning task for the next project milestone.',
                'workflow_type' => 'planning',
                'recommended_agent' => 'Planner',
                'needs_clarification' => false,
                'clarifying_questions' => [],
                'goals' => ['Define milestone'],
                'acceptance_criteria' => ['Milestone documented'],
            ],
        ],
    ]);

    $agent = new CoordinatorAgent(createMockAiService());

    $result = $agent->orchestrateRequest('Please plan the next milestone.');

    expect($result['created_task'])->toBeFalse()
        ->and($result['task_type'])->toBe('planning')
        ->and($result['task'])->toBeNull()
        ->and($result['response'])->toContain('create it');

    expect(ProcessLog::query()->where('kind', 'draft.created')->exists())->toBeTrue();
});

it('creates a task from the interpreted draft after confirmation', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);

    $projectService = app(ProjectService::class);
    $conversationService = app(ProjectConversationService::class);
    $project = $projectService->ensureInboxProject();
    $conversation = $conversationService->ensureDefaultConversation($project);
    $conversationService->storePendingTaskDraft($conversation, [
        'title' => 'Plan the next milestone',
        'summary' => 'Define the next project milestone and success conditions.',
        'description' => 'Create a planning task for the next project milestone.',
        'workflow_type' => 'planning',
        'recommended_agent' => 'Planner',
        'needs_clarification' => false,
        'clarifying_questions' => [],
        'goals' => ['Define milestone'],
        'acceptance_criteria' => ['Milestone documented'],
    ]);
    ClawraCoordinatorConversationAgent::fake([
        [
            'action' => 'create_task',
            'response' => 'That is ready. I will create the task now.',
            'draft' => null,
        ],
    ]);

    $agent = new CoordinatorAgent(
        createMockAiService(),
        $projectService,
        app(TaskService::class),
        app(WorkflowService::class),
    );

    $result = $agent->orchestrateRequest('confirm', $project->id, $conversation->id);

    expect($result['created_task'])->toBeTrue()
        ->and($result['task'])->not->toBeNull()
        ->and($result['task']->name)->toBe('Plan the next milestone')
        ->and($result['task']->recommended_agent_id)->not->toBeNull()
        ->and($result['task']->recommendedAgent?->name)->toBe('Planner')
        ->and($result['task']->description)->toContain('Recommended starting agent: Planner');

    expect(ProcessLog::query()->where('kind', 'task.created')->exists())->toBeTrue();
});

it('creates a task from a ready draft when the confirmation is phrased naturally', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);

    $projectService = app(ProjectService::class);
    $conversationService = app(ProjectConversationService::class);
    $project = $projectService->ensureInboxProject();
    $conversation = $conversationService->ensureDefaultConversation($project);
    $conversationService->storePendingTaskDraft($conversation, [
        'title' => 'Plan the next milestone',
        'summary' => 'Define the next project milestone and success conditions.',
        'description' => 'Create a planning task for the next project milestone.',
        'workflow_type' => 'planning',
        'recommended_agent' => 'Planner',
        'needs_clarification' => false,
        'clarifying_questions' => [],
        'goals' => ['Define milestone'],
        'acceptance_criteria' => ['Milestone documented'],
    ]);
    ClawraCoordinatorConversationAgent::fake([
        [
            'action' => 'create_task',
            'response' => 'That is ready. I will create the task now.',
            'draft' => null,
        ],
    ]);

    $agent = new CoordinatorAgent(
        createMockAiService(),
        $projectService,
        app(TaskService::class),
        app(WorkflowService::class),
    );

    $result = $agent->orchestrateRequest('Yes, that looks good - create it.', $project->id, $conversation->id);

    expect($result['created_task'])->toBeTrue()
        ->and($result['task'])->not->toBeNull()
        ->and($conversation->fresh()->state_document['pending_task_draft'] ?? null)->toBeNull();
});

it('keeps conversational responses task-free when the intent agent says chat', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);
    ClawraCoordinatorConversationAgent::fake([
        ['action' => 'chat', 'response' => 'Hello from Clawra.', 'draft' => null],
    ]);

    $agent = new CoordinatorAgent(createMockAiService());

    $result = $agent->orchestrateRequest('hi');

    expect($result['created_task'])->toBeFalse()
        ->and($result['task_type'])->toBe('chat')
        ->and($result['response'])->toBe('Hello from Clawra.');
});

it('stores concise research summaries instead of raw long search output', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);
    ClawraCoordinatorConversationAgent::fake([
        [
            'action' => 'draft',
            'response' => 'I drafted a research task. Tell me to create it when you are ready.',
            'draft' => [
                'title' => 'Investigate synthetic rate limits',
                'summary' => str_repeat('Very long research summary. ', 40),
                'description' => 'Investigate synthetic rate limits and document the findings.',
                'workflow_type' => 'research',
                'recommended_agent' => 'Researcher',
                'needs_clarification' => false,
                'clarifying_questions' => [],
                'goals' => ['Capture limits'],
                'acceptance_criteria' => ['Findings documented'],
            ],
        ],
    ]);

    $agent = new CoordinatorAgent(createMockAiService());

    $result = $agent->orchestrateRequest('Please investigate synthetic rate limits.');

    expect($result['created_task'])->toBeFalse()
        ->and(strlen((string) $result['artifact']['draft']['summary']))->toBeLessThanOrEqual(230)
        ->and(strlen((string) json_encode(app(ProjectConversationService::class)->ensureDefaultConversation($result['project'])->fresh()->state_document)))->toBeLessThan(5000);
});

it('cancels a pending draft when the conversation agent decides to cancel it', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);

    $projectService = app(ProjectService::class);
    $conversationService = app(ProjectConversationService::class);
    $project = $projectService->ensureInboxProject();
    $conversation = $conversationService->ensureDefaultConversation($project);
    $conversationService->storePendingTaskDraft($conversation, [
        'title' => 'Plan the next milestone',
        'summary' => 'Define the next project milestone and success conditions.',
        'description' => 'Create a planning task for the next project milestone.',
        'workflow_type' => 'planning',
        'recommended_agent' => 'Planner',
        'needs_clarification' => false,
        'clarifying_questions' => [],
        'goals' => ['Define milestone'],
        'acceptance_criteria' => ['Milestone documented'],
    ]);
    ClawraCoordinatorConversationAgent::fake([
        [
            'action' => 'cancel_draft',
            'response' => 'Okay, I cleared that draft.',
            'draft' => null,
        ],
    ]);

    $agent = new CoordinatorAgent(createMockAiService(), $projectService, app(TaskService::class), app(WorkflowService::class));

    $result = $agent->orchestrateRequest('Actually, drop that task idea.', $project->id, $conversation->id);

    expect($result['created_task'])->toBeFalse()
        ->and($conversation->fresh()->state_document['pending_task_draft'] ?? null)->toBeNull()
        ->and(ProcessLog::query()->where('kind', 'draft.cancelled')->exists())->toBeTrue();
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
