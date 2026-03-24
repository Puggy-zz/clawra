<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Sandbox;
use App\Models\Task;
use App\Services\SandboxManagerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('can be instantiated', function () {
    expect(app(SandboxManagerService::class))->toBeInstanceOf(SandboxManagerService::class);
});

it('provisionForTask creates a sandbox record and runs docker sandbox create, git clone, and git checkout', function () {
    Process::fake([
        '*' => Process::result(output: '', exitCode: 0),
    ]);

    $project = Project::factory()->create([
        'git_remote_url' => 'https://github.com/example/repo.git',
    ]);
    $task = Task::factory()->create(['project_id' => $project->id]);

    $sandbox = app(SandboxManagerService::class)->provisionForTask($task);

    expect($sandbox)->toBeInstanceOf(Sandbox::class)
        ->and($sandbox->sandbox_id)->toBe('clawra-task-'.$task->id)
        ->and($sandbox->task_id)->toBe($task->id)
        ->and($sandbox->status)->toBe('active');

    $cmd = fn (PendingProcess $p): string => is_array($p->command) ? implode(' ', $p->command) : (string) $p->command;

    Process::assertRan(fn (PendingProcess $p) => str_contains($cmd($p), 'sandbox create'));
    Process::assertRan(fn (PendingProcess $p) => str_contains($cmd($p), 'git clone'));
    Process::assertRan(fn (PendingProcess $p) => str_contains($cmd($p), 'git checkout -b clawra/task-'.$task->id));
});

it('provisionForTask throws when docker sandbox create fails', function () {
    Process::fake([
        '*' => Process::result(exitCode: 1, errorOutput: 'daemon not running'),
    ]);

    $task = Task::factory()->create();

    expect(fn () => app(SandboxManagerService::class)->provisionForTask($task))
        ->toThrow(RuntimeException::class, 'docker sandbox create failed');
});

it('provisionForTask throws and marks sandbox failed when git clone fails', function () {
    Process::fake([
        '*' => Process::sequence()
            ->push(Process::result(output: '', exitCode: 0)) // docker sandbox create
            ->push(Process::result(exitCode: 128, errorOutput: 'repository not found')) // git clone
            ->whenEmpty(Process::result(exitCode: 0)),
    ]);

    $project = Project::factory()->create(['git_remote_url' => 'https://github.com/example/repo.git']);
    $task = Task::factory()->create(['project_id' => $project->id]);

    expect(fn () => app(SandboxManagerService::class)->provisionForTask($task))
        ->toThrow(RuntimeException::class, 'git clone failed');

    expect(Sandbox::query()->where('task_id', $task->id)->value('status'))->toBe('failed');
});

it('exec runs docker sandbox exec with env flags and workingDir', function () {
    Process::fake(['*' => Process::result(output: 'ok', exitCode: 0)]);

    $sandbox = Sandbox::factory()->create(['sandbox_id' => 'sb-xyz']);

    $result = app(SandboxManagerService::class)->exec(
        $sandbox,
        'echo hello',
        ['FOO' => 'bar'],
        '/workspace',
    );

    expect(trim($result->output()))->toBe('ok');

    Process::assertRan(function (PendingProcess $p) {
        $cmd = is_array($p->command) ? implode(' ', $p->command) : (string) $p->command;

        return str_contains($cmd, 'sandbox exec')
            && str_contains($cmd, 'sb-xyz')
            && str_contains($cmd, '--env')
            && str_contains($cmd, 'FOO=bar')
            && str_contains($cmd, '--workdir')
            && str_contains($cmd, '/workspace');
    });
});

it('isRunning returns true when inspect exits zero', function () {
    Process::fake(['*' => Process::result(exitCode: 0)]);

    $sandbox = Sandbox::factory()->create(['sandbox_id' => 'sb-running']);

    expect(app(SandboxManagerService::class)->isRunning($sandbox))->toBeTrue();
});

it('isRunning returns false when inspect exits non-zero', function () {
    Process::fake(['*' => Process::result(exitCode: 1)]);

    $sandbox = Sandbox::factory()->create(['sandbox_id' => 'sb-gone']);

    expect(app(SandboxManagerService::class)->isRunning($sandbox))->toBeFalse();
});

it('remove runs docker sandbox rm and sets status to inactive', function () {
    Process::fake(['*' => Process::result(exitCode: 0)]);

    $sandbox = Sandbox::factory()->create(['sandbox_id' => 'sb-old', 'status' => 'active']);

    app(SandboxManagerService::class)->remove($sandbox);

    expect($sandbox->refresh()->status)->toBe('inactive');

    Process::assertRan(function (PendingProcess $p) {
        $cmd = is_array($p->command) ? implode(' ', $p->command) : (string) $p->command;

        return str_contains($cmd, 'sandbox rm') && str_contains($cmd, 'sb-old');
    });
});
