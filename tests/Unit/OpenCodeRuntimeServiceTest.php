<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\AgentRuntime;
use App\Models\Provider;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use App\Services\OpenCodeRuntimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('runs opencode with the configured agent and model', function () {
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
    $runtime = AgentRuntime::factory()->create([
        'agent_id' => $agent->id,
        'provider_route_id' => $route->id,
        'provider_model_id' => $model->id,
        'harness' => 'opencode',
        'runtime_type' => 'opencode_agent',
        'runtime_ref' => 'build',
        'name' => 'builder',
    ]);

    Process::fake([
        '*' => Process::result('{"text":"OpenCode completed the task."}'),
    ]);

    $service = app(OpenCodeRuntimeService::class);

    $result = $service->execute($runtime, 'Implement the feature');

    expect($result['success'])->toBeTrue()
        ->and($result['text'])->toBe('OpenCode completed the task.');

    Process::assertRan(function (PendingProcess $process) {
        return str_contains($process->command, 'opencode run')
            && str_contains($process->command, '--agent build')
            && str_contains($process->command, '--model openai/gpt-5.4');
    });
});
