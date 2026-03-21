<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\AgentRuntime;
use App\Models\ExternalSession;
use App\Models\ExternalSessionEvent;
use App\Models\ProcessLog;
use App\Models\Provider;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use App\Services\RuntimeExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('persists opencode session references and append only logs', function () {
    $provider = Provider::factory()->create(['name' => 'openai']);
    $route = ProviderRoute::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'openai-opencode-chatgpt',
        'harness' => 'opencode',
        'auth_mode' => 'chatgpt_oauth',
    ]);
    $model = ProviderModel::factory()->create([
        'provider_route_id' => $route->id,
        'name' => 'gpt-5.4',
        'external_name' => 'openai/gpt-5.4',
    ]);
    $agent = Agent::factory()->create(['name' => 'Developer']);
    AgentRuntime::factory()->create([
        'agent_id' => $agent->id,
        'provider_route_id' => $route->id,
        'provider_model_id' => $model->id,
        'harness' => 'opencode',
        'runtime_type' => 'opencode_agent',
        'runtime_ref' => 'build',
        'name' => 'builder',
        'is_default' => true,
    ]);

    Process::fake([
        '*' => Process::result(json_encode([
            'text' => 'OpenCode completed the task.',
            'session' => ['id' => 'sess_123', 'title' => 'Developer build run'],
            'events' => [
                ['type' => 'session.started', 'id' => 'evt_1'],
                ['type' => 'message.completed', 'id' => 'evt_2'],
            ],
        ], JSON_THROW_ON_ERROR)),
    ]);

    $result = app(RuntimeExecutionService::class)->executeAgent('Developer', 'Implement the feature');

    expect($result['success'])->toBeTrue()
        ->and($result['external_session_ref'])->toBe('sess_123');

    expect(ExternalSession::query()->where('external_id', 'sess_123')->exists())->toBeTrue()
        ->and(ExternalSessionEvent::query()->count())->toBe(2)
        ->and(ProcessLog::query()->where('kind', 'runtime.execution.started')->count())->toBe(1)
        ->and(ProcessLog::query()->where('kind', 'runtime.execution.completed')->count())->toBe(1);
});
