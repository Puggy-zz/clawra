<?php

declare(strict_types=1);

use App\Ai\Agents\ClawraCoordinatorConversationAgent;
use App\Models\Agent;
use App\Models\AgentRuntime;
use App\Models\Project;
use App\Models\Provider;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use App\Models\Task;
use App\Services\ProjectConversationService;
use App\Services\ProjectService;
use Database\Seeders\AgentSeeder;
use Database\Seeders\ProviderSeeder;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('can display the welcome page', function () {
    $this->get('/')->assertSuccessful()->assertSee('Clawra');
});

it('can display the coordinator interface', function () {
    $this->seed([ProviderSeeder::class, WorkflowSeeder::class]);

    $this->get('/coordinator')
        ->assertSuccessful()
        ->assertSee('Clawra Phase 0 Control Room')
        ->assertSee('Pending Draft')
        ->assertSee('Activity')
        ->assertSee('Agents')
        ->assertSee('Providers')
        ->assertSee('Configured routes and models')
        ->assertSee('Tracked Tasks');
});

it('can create a provider from the coordinator interface', function () {
    $this->seed([ProviderSeeder::class, WorkflowSeeder::class]);

    $this->from('/coordinator')->post('/coordinator/providers', [
        'name' => 'backup-provider',
        'type' => 'hybrid',
        'api_protocol' => 'native',
        'status' => 'degraded',
        'capability_tags_text' => 'chat, fallback',
        'priority_preferences_text' => '{"chat": 2, "fallback": 1}',
        'rate_limits_text' => '{"requests_per_window": 25}',
        'usage_snapshot_text' => '{"requests_remaining": 25}',
    ])->assertRedirect('/coordinator');

    $provider = Provider::query()->where('name', 'backup-provider')->first();

    expect($provider)->not->toBeNull()
        ->and($provider?->capability_tags)->toBe(['chat', 'fallback'])
        ->and($provider?->priority_preferences)->toBe(['chat' => 2, 'fallback' => 1]);
});

it('can update an agent from the coordinator interface', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);

    $agent = Agent::query()->where('name', 'Planner')->firstOrFail();

    $this->from('/coordinator')->patch("/coordinator/agents/{$agent->id}", [
        'name' => 'Planner',
        'role' => 'Task Planning Specialist',
        'description' => 'Updated planner configuration.',
        'tools_text' => 'task_planning, estimation',
    ])->assertRedirect('/coordinator');

    $freshAgent = $agent->fresh();

    expect($freshAgent?->description)->toBe('Updated planner configuration.')
        ->and($freshAgent?->tools)->toBe(['task_planning', 'estimation']);
});

it('can create an agent runtime from the coordinator interface', function () {
    $this->seed([ProviderSeeder::class, AgentSeeder::class, WorkflowSeeder::class]);

    $agent = Agent::query()->where('name', 'Planner')->firstOrFail();
    $route = ProviderRoute::query()->where('name', 'synthetic-opencode')->firstOrFail();
    $model = ProviderModel::query()->where('provider_route_id', $route->id)->where('name', 'qwen3-coder')->firstOrFail();

    $this->from('/coordinator')->post('/coordinator/agent-runtimes', [
        'agent_id' => $agent->id,
        'name' => 'planner-experimental',
        'harness' => 'opencode',
        'runtime_type' => 'opencode_agent',
        'runtime_ref' => 'plan',
        'provider_route_id' => $route->id,
        'provider_model_id' => $model->id,
        'tools_text' => 'task_planning',
        'config_text' => '{"sandbox":"workspace-write"}',
        'status' => 'active',
        'is_default' => '1',
    ])->assertRedirect('/coordinator');

    $runtime = AgentRuntime::query()->where('name', 'planner-experimental')->first();

    expect($runtime)->not->toBeNull()
        ->and($runtime?->harness)->toBe('opencode')
        ->and($runtime?->provider_route_id)->toBe($route->id)
        ->and($runtime?->provider_model_id)->toBe($model->id);
});

it('can create a provider model from the coordinator interface', function () {
    $this->seed([ProviderSeeder::class, WorkflowSeeder::class]);

    $route = ProviderRoute::query()->where('name', 'gemini-opencode')->firstOrFail();

    $this->from('/coordinator')->post('/coordinator/provider-models', [
        'provider_route_id' => $route->id,
        'name' => 'gemini-2.5-flash-lite',
        'external_name' => 'google/gemini-2.5-flash-lite',
        'capabilities_text' => 'chat, coding',
        'context_window' => 512000,
        'priority' => 55,
        'config_text' => '{"temperature":0.2}',
        'status' => 'active',
    ])->assertRedirect('/coordinator');

    expect(ProviderModel::query()->where('name', 'gemini-2.5-flash-lite')->exists())->toBeTrue();
});

it('returns a success payload for coordinator requests', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);
    ClawraCoordinatorConversationAgent::fake([
        [
            'action' => 'propose_tasks',
            'response' => 'I proposed a planning task list.',
            'tasks' => [
                [
                    'name' => 'Plan provider fallback validation',
                    'description' => 'Define the milestones for provider fallback validation.',
                    'workflow_type' => 'planning',
                    'recommended_agent' => 'Planner',
                ],
            ],
        ],
    ]);

    $response = $this->postJson('/coordinator/message', [
        'message' => 'Please plan phase 0 milestones for provider fallback validation.',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('meta.created_task', false)
        ->assertJsonStructure([
            'status',
            'response',
            'meta' => ['created_task', 'task_type', 'project', 'task', 'recommended_agent', 'pending_task_list', 'process_logs', 'external_sessions'],
            'timestamp',
        ]);
});

it('stores the proposed task list in the conversation state document', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);

    $project = app(ProjectService::class)->ensureInboxProject();
    $conversation = app(ProjectConversationService::class)->ensureDefaultConversation($project);
    ClawraCoordinatorConversationAgent::fake([
        [
            'action' => 'propose_tasks',
            'response' => 'Here are the tasks.',
            'tasks' => [
                [
                    'name' => 'Plan provider fallback validation',
                    'description' => 'Define the milestones for provider fallback validation.',
                    'workflow_type' => 'planning',
                    'recommended_agent' => 'Planner',
                ],
            ],
        ],
    ]);

    $response = $this->postJson('/coordinator/message', [
        'message' => 'Plan phase 0 milestones.',
        'project_id' => $project->id,
        'conversation_id' => $conversation->id,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('meta.created_task', false);

    $stored = app(ProjectConversationService::class)->getPendingTaskList($conversation->fresh());

    expect($stored)->toHaveCount(1)
        ->and($stored[0]['name'])->toBe('Plan provider fallback validation');
});

it('returns the task list in meta when the agent proposes tasks', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);
    ClawraCoordinatorConversationAgent::fake([
        [
            'action' => 'propose_tasks',
            'response' => 'Here are two tasks.',
            'tasks' => [
                [
                    'name' => 'Plan provider fallback validation',
                    'description' => 'Define the milestones.',
                    'workflow_type' => 'planning',
                    'recommended_agent' => 'Planner',
                ],
                [
                    'name' => 'Research retry timing',
                    'description' => 'Identify retry intervals.',
                    'workflow_type' => 'research',
                    'recommended_agent' => 'Researcher',
                ],
            ],
        ],
    ]);

    $response = $this->postJson('/coordinator/message', [
        'message' => 'Plan the next phase.',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('meta.created_task', false)
        ->assertJsonCount(2, 'meta.pending_task_list');
});

it('keeps conversation messages in a stable order on refresh', function () {
    $this->seed([ProviderSeeder::class, WorkflowSeeder::class]);

    $project = app(ProjectService::class)->ensureInboxProject();
    $conversation = app(ProjectConversationService::class)->ensureDefaultConversation($project);

    $conversation->update([
        'laravel_ai_conversation_id' => 'conv-stable-order',
    ]);

    $timestamp = now()->toDateTimeString();

    DB::table('agent_conversation_messages')->insert([
        [
            'id' => (string) Str::uuid(),
            'conversation_id' => 'conv-stable-order',
            'agent' => 'Clawra',
            'role' => 'user',
            'content' => 'Please create the task.',
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '{}',
            'meta' => '{}',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
        [
            'id' => (string) Str::uuid(),
            'conversation_id' => 'conv-stable-order',
            'agent' => 'Clawra',
            'role' => 'assistant',
            'content' => 'Done, I created it.',
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '{}',
            'meta' => '{}',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ],
    ]);

    $messages = app(ProjectConversationService::class)->getMessages($conversation->fresh());

    expect($messages)->toHaveCount(2)
        ->and($messages[0]['role'])->toBe('user')
        ->and($messages[0]['content'])->toBe('Please create the task.')
        ->and($messages[1]['role'])->toBe('assistant')
        ->and($messages[1]['content'])->toBe('Done, I created it.');

    $this->get('/coordinator?project_id='.$project->id.'&conversation_id='.$conversation->id)
        ->assertSuccessful()
        ->assertSee('Please create the task.')
        ->assertSee('Done, I created it.');
});

it('shows the current pending draft on the coordinator page', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);

    $project = app(ProjectService::class)->ensureInboxProject();
    $conversation = app(ProjectConversationService::class)->ensureDefaultConversation($project);
    app(ProjectConversationService::class)->storePendingTaskDraft($conversation, [
        'title' => 'Investigate fallback timing',
        'summary' => 'Clarify the fallback timing behavior before creating the task.',
        'description' => 'Discuss retry timing and expected behavior.',
        'workflow_type' => 'research',
        'recommended_agent' => 'Researcher',
        'recommended_agent_id' => Agent::query()->where('name', 'Researcher')->first()?->id,
        'needs_clarification' => true,
        'clarifying_questions' => ['Which provider path should we test first?'],
        'goals' => [],
        'acceptance_criteria' => [],
    ]);

    $this->get('/coordinator?project_id='.$project->id.'&conversation_id='.$conversation->id)
        ->assertSuccessful()
        ->assertSee('Investigate fallback timing')
        ->assertSee('Researcher')
        ->assertSee('needs clarification');
});

it('responds conversationally without creating a task for greetings', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);
    ClawraCoordinatorConversationAgent::fake([
        ['action' => 'chat', 'response' => 'Hello from the coordinator agent.', 'tasks' => null],
    ]);

    $response = $this->postJson('/coordinator/message', [
        'message' => 'hi',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('meta.created_task', false)
        ->assertJsonPath('meta.task_type', 'chat')
        ->assertJsonPath('response', 'Hello from the coordinator agent.');

    expect(Task::query()->count())->toBe(0);
});

it('clears the pending task list when the conversation agent decides to cancel it', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);

    $project = app(ProjectService::class)->ensureInboxProject();
    $conversation = app(ProjectConversationService::class)->ensureDefaultConversation($project);
    app(ProjectConversationService::class)->storePendingTaskList($conversation, [
        ['name' => 'Plan provider fallback validation', 'description' => 'Define milestones.', 'workflow_type' => 'planning', 'recommended_agent' => 'Planner'],
    ]);
    ClawraCoordinatorConversationAgent::fake([
        [
            'action' => 'cancel_tasks',
            'response' => 'Okay, I cleared those tasks.',
            'tasks' => null,
        ],
    ]);

    $response = $this->postJson('/coordinator/message', [
        'message' => 'Actually, drop those task ideas.',
        'project_id' => $project->id,
        'conversation_id' => $conversation->id,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('meta.created_task', false)
        ->assertJsonPath('meta.pending_task_list', null);

    expect(app(ProjectConversationService::class)->getPendingTaskList($conversation->fresh()))->toBeEmpty();
});

it('can create a project conversation from the coordinator interface', function () {
    $this->seed([ProviderSeeder::class, WorkflowSeeder::class]);
    $project = Project::factory()->create(['name' => 'Website Refresh']);

    $response = $this->post('/coordinator/conversations', [
        'project_id' => $project->id,
        'title' => 'Bugfix triage',
        'purpose' => 'bugfix',
    ]);

    $conversation = \App\Models\ProjectConversation::query()->where('title', 'Bugfix triage')->first();

    expect($conversation)->not->toBeNull()
        ->and($conversation?->project_id)->toBe($project->id);

    $response->assertRedirect('/coordinator?project_id='.$project->id.'&conversation_id='.$conversation->id);
});

it('rejects empty messages', function () {
    $this->postJson('/coordinator/message', ['message' => ''])->assertStatus(422);
});

it('rejects messages that are too long', function () {
    $this->postJson('/coordinator/message', ['message' => str_repeat('a', 1001)])->assertStatus(422);
});

it('can update a task from the coordinator interface', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);

    $project = Project::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'name' => 'Original task',
        'description' => 'Old description',
        'status' => 'pending',
    ]);

    $this->from('/coordinator')->patch("/coordinator/tasks/{$task->id}", [
        'name' => 'Updated task',
        'description' => 'Updated description',
        'status' => 'completed',
    ])->assertRedirect('/coordinator');

    expect($task->fresh())
        ->name->toBe('Updated task')
        ->description->toBe('Updated description')
        ->status->toBe('completed');
});

it('can delete a task from the coordinator interface', function () {
    $this->seed([AgentSeeder::class, ProviderSeeder::class, WorkflowSeeder::class]);

    $project = Project::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'status' => 'pending',
    ]);

    $this->from('/coordinator')->delete("/coordinator/tasks/{$task->id}")
        ->assertRedirect('/coordinator');

    expect(Task::query()->find($task->id))->toBeNull();
});
