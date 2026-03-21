<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\AgentRuntime;
use App\Models\Provider;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use App\Services\ClaudeCodeRuntimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;

uses(Tests\TestCase::class, RefreshDatabase::class);

function makeClaudeCodeRuntime(?string $externalName = 'claude-sonnet-4-6'): AgentRuntime
{
    $provider = Provider::factory()->create(['name' => 'anthropic']);
    $route = ProviderRoute::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'anthropic-claude-code',
        'harness' => 'claude_code',
        'auth_mode' => 'provider_oauth',
    ]);
    $model = ProviderModel::factory()->create([
        'provider_route_id' => $route->id,
        'name' => 'claude-sonnet-4-6',
        'external_name' => $externalName,
    ]);
    $agent = Agent::factory()->create(['name' => 'Developer']);

    return AgentRuntime::factory()->create([
        'agent_id' => $agent->id,
        'provider_route_id' => $route->id,
        'provider_model_id' => $model->id,
        'harness' => 'claude_code',
        'runtime_type' => 'claude_code_agent',
        'runtime_ref' => 'claude-code-runner',
        'name' => 'developer-claude-code',
    ]);
}

it('returns success when runner outputs valid JSON with result', function () {
    $runtime = makeClaudeCodeRuntime();

    Process::fake([
        '*' => Process::result(json_encode([
            'success' => true,
            'result' => 'Task completed successfully.',
            'session_id' => 'sess-abc123',
            'stop_reason' => 'end_turn',
        ])),
    ]);

    $service = app(ClaudeCodeRuntimeService::class);
    $result = $service->execute($runtime, 'Implement the feature');

    expect($result['success'])->toBeTrue()
        ->and($result['text'])->toBe('Task completed successfully.')
        ->and($result['status'])->toBe('completed')
        ->and($result['external_session'])->toMatchArray([
            'harness' => 'claude_code',
            'external_id' => 'sess-abc123',
        ]);

    Process::assertRan(function (PendingProcess $process) {
        return str_contains($process->command, 'claude-code-runner.mjs')
            && str_contains($process->command, '--model claude-sonnet-4-6')
            && isset($process->environment['CLAWRA_PROMPT']);
    });
});

it('returns failure when the process exits with non-zero status', function () {
    $runtime = makeClaudeCodeRuntime();

    Process::fake([
        '*' => Process::result(
            output: json_encode(['success' => false, 'error' => 'Rate limit exceeded.', 'session_id' => null]),
            exitCode: 1,
        ),
    ]);

    $service = app(ClaudeCodeRuntimeService::class);
    $result = $service->execute($runtime, 'Implement the feature');

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe('failed')
        ->and($result['error'])->toBe('Rate limit exceeded.');
});

it('returns failure when output is malformed JSON', function () {
    $runtime = makeClaudeCodeRuntime();

    Process::fake([
        '*' => Process::result('not valid json at all'),
    ]);

    $service = app(ClaudeCodeRuntimeService::class);
    $result = $service->execute($runtime, 'Implement the feature');

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe('failed')
        ->and($result['error'])->toContain('Malformed JSON output');
});

it('returns null external_session when session_id is missing', function () {
    $runtime = makeClaudeCodeRuntime();

    Process::fake([
        '*' => Process::result(json_encode([
            'success' => true,
            'result' => 'Done.',
            'session_id' => null,
            'stop_reason' => 'end_turn',
        ])),
    ]);

    $service = app(ClaudeCodeRuntimeService::class);
    $result = $service->execute($runtime, 'Implement the feature');

    expect($result['success'])->toBeTrue()
        ->and($result['external_session'])->toBeNull()
        ->and($result['external_events'])->toBe([]);
});
