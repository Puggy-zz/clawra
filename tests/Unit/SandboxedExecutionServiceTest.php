<?php

declare(strict_types=1);

use App\Models\AgentRuntime;
use App\Models\Sandbox;
use App\Models\Task;
use App\Services\SandboxedExecutionService;
use App\Services\SandboxManagerService;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;

uses(Tests\TestCase::class, RefreshDatabase::class);

function makeFakeProcessResult(string $output = '', int $exitCode = 0): ProcessResult
{
    $mock = Mockery::mock(ProcessResult::class);
    $mock->allows('output')->andReturn($output);
    $mock->allows('exitCode')->andReturn($exitCode);
    $mock->allows('successful')->andReturn($exitCode === 0);
    $mock->allows('errorOutput')->andReturn('');

    return $mock;
}

function makeSandboxedService(SandboxManagerService $manager): SandboxedExecutionService
{
    return app(SandboxedExecutionService::class, ['sandboxManager' => $manager]);
}

it('throws when task is null', function () {
    $runtime = AgentRuntime::factory()->make(['harness' => 'opencode', 'sandboxed' => true, 'config' => []]);

    expect(fn () => app(SandboxedExecutionService::class)->execute($runtime, 'do work'))
        ->toThrow(RuntimeException::class, 'SandboxedExecutionService requires a Task instance');
});

it('finds an existing active sandbox for the task', function () {
    $task = Task::factory()->create();
    Sandbox::factory()->create(['task_id' => $task->id, 'sandbox_id' => 'existing-sb', 'status' => 'active']);

    $manager = Mockery::mock(SandboxManagerService::class);
    $manager->allows('exec')->andReturn(makeFakeProcessResult('{"type":"text","text":"done"}'));
    $manager->shouldNotReceive('provisionForTask');

    $runtime = AgentRuntime::factory()->make(['harness' => 'opencode', 'sandboxed' => true, 'config' => []]);

    $result = makeSandboxedService($manager)->execute($runtime, 'do work', null, $task);

    expect($result)->toHaveKey('status');
});

it('provisions a new sandbox when none exists for the task', function () {
    $task = Task::factory()->create();
    $sandbox = Sandbox::factory()->make(['task_id' => $task->id, 'sandbox_id' => 'new-sb', 'status' => 'active']);

    $manager = Mockery::mock(SandboxManagerService::class);
    $manager->allows('provisionForTask')->with($task)->andReturn($sandbox);
    $manager->allows('exec')->andReturn(makeFakeProcessResult(''));

    $runtime = AgentRuntime::factory()->make(['harness' => 'opencode', 'sandboxed' => true, 'config' => []]);

    $result = makeSandboxedService($manager)->execute($runtime, 'do work', null, $task);

    expect($result)->toHaveKey('status');
});

it('builds the correct opencode command from harness', function () {
    $task = Task::factory()->create();
    Sandbox::factory()->create(['task_id' => $task->id, 'sandbox_id' => 'sb1', 'status' => 'active']);

    $capturedCommand = null;
    $manager = Mockery::mock(SandboxManagerService::class);
    $manager->allows('exec')->withArgs(function ($s, $cmd) use (&$capturedCommand) {
        $capturedCommand = $cmd;

        return true;
    })->andReturn(makeFakeProcessResult(''));

    $runtime = AgentRuntime::factory()->make([
        'harness' => 'opencode',
        'sandboxed' => true,
        'config' => ['opencode_agent' => 'build'],
    ]);

    makeSandboxedService($manager)->execute($runtime, 'implement feature', null, $task);

    expect($capturedCommand)->toContain('opencode run')
        ->and($capturedCommand)->toContain('--format json')
        ->and($capturedCommand)->toContain('--agent build');
});

it('runs claude_code via Agent SDK on host with CLAWRA_PROMPT env var', function () {
    $task = Task::factory()->create();
    Sandbox::factory()->create(['task_id' => $task->id, 'sandbox_id' => 'sb2', 'name' => 'sb2', 'status' => 'active']);

    Process::fake(['*' => Process::result('{"success":true,"result":"done"}', exitCode: 0)]);

    $manager = Mockery::mock(SandboxManagerService::class);
    $manager->shouldNotReceive('exec');

    $runtime = AgentRuntime::factory()->make([
        'harness' => 'claude_code',
        'sandboxed' => true,
        'config' => [],
    ]);

    $result = makeSandboxedService($manager)->execute($runtime, 'fix the bug', null, $task);

    expect($result['success'])->toBeTrue();

    Process::assertRan(function (Illuminate\Process\PendingProcess $p) {
        $cmd = is_array($p->command) ? implode(' ', $p->command) : (string) $p->command;

        return str_contains($cmd, 'claude-code-runner.mjs')
            && str_contains($cmd, '--cwd')
            && ($p->environment['CLAWRA_PROMPT'] ?? null) === 'fix the bug';
    });
});

it('returns failure on non-zero exit code', function () {
    $task = Task::factory()->create();
    Sandbox::factory()->create(['task_id' => $task->id, 'sandbox_id' => 'sb3', 'status' => 'active']);

    $manager = Mockery::mock(SandboxManagerService::class);
    $manager->allows('exec')->andReturn(makeFakeProcessResult('', 1));

    $runtime = AgentRuntime::factory()->make(['harness' => 'opencode', 'sandboxed' => true, 'config' => []]);

    $result = makeSandboxedService($manager)->execute($runtime, 'do work', null, $task);

    expect($result['success'])->toBeFalse()
        ->and($result['status'])->toBe('failed');
});

it('marks sandbox failed on exit code 125', function () {
    $task = Task::factory()->create();
    $sandbox = Sandbox::factory()->create(['task_id' => $task->id, 'sandbox_id' => 'sb4', 'status' => 'active']);

    $manager = Mockery::mock(SandboxManagerService::class);
    $manager->allows('exec')->andReturn(makeFakeProcessResult('', 125));

    $runtime = AgentRuntime::factory()->make(['harness' => 'opencode', 'sandboxed' => true, 'config' => []]);

    makeSandboxedService($manager)->execute($runtime, 'do work', null, $task);

    expect($sandbox->refresh()->status)->toBe('failed');
});

it('marks sandbox failed on exit code 126', function () {
    $task = Task::factory()->create();
    $sandbox = Sandbox::factory()->create(['task_id' => $task->id, 'sandbox_id' => 'sb5', 'status' => 'active']);

    $manager = Mockery::mock(SandboxManagerService::class);
    $manager->allows('exec')->andReturn(makeFakeProcessResult('', 126));

    $runtime = AgentRuntime::factory()->make(['harness' => 'opencode', 'sandboxed' => true, 'config' => []]);

    makeSandboxedService($manager)->execute($runtime, 'do work', null, $task);

    expect($sandbox->refresh()->status)->toBe('failed');
});

it('uses runtime harness to pick the codex command builder', function () {
    $task = Task::factory()->create();
    Sandbox::factory()->create(['task_id' => $task->id, 'sandbox_id' => 'sb6', 'status' => 'active']);

    $capturedCommand = null;
    $manager = Mockery::mock(SandboxManagerService::class);
    $manager->allows('exec')->withArgs(function ($s, $cmd) use (&$capturedCommand) {
        $capturedCommand = $cmd;

        return true;
    })->andReturn(makeFakeProcessResult('task completed'));

    $runtime = AgentRuntime::factory()->make(['harness' => 'codex', 'sandboxed' => true, 'config' => []]);

    $result = makeSandboxedService($manager)->execute($runtime, 'do something', null, $task);

    expect($capturedCommand)->toStartWith('codex')
        ->and($result['success'])->toBeTrue();
});
