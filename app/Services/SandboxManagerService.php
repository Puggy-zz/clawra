<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Sandbox;
use App\Models\Task;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class SandboxManagerService
{
    public function __construct(protected ProcessLogService $processLogService) {}

    /**
     * Provision a new Docker sandbox for the given task.
     * Creates the sandbox from the template image, then clones the project's
     * git remote and checks out a feature branch inside the sandbox.
     */
    public function provisionForTask(Task $task): Sandbox
    {
        $project = $task->project;
        $name = 'clawra-task-'.$task->id;
        $template = $task->recommendedAgent?->defaultRuntime?->config['sandbox_image']
            ?? (string) config('services.docker_sandbox.image', 'clawra-sandbox:latest');
        $binary = (string) config('services.docker_sandbox.binary', 'docker');
        $timeout = (int) config('services.docker_sandbox.timeout', 600);

        // Docker sandbox requires a host workspace path. We use a per-task temp
        // directory so nothing syncs back to the actual project on the host.
        $hostWorkspace = storage_path('app/sandboxes/'.$name);
        File::ensureDirectoryExists($hostWorkspace);

        $createResult = Process::timeout($timeout)
            ->run([$binary, 'sandbox', 'create', '-t', $template, '--name', $name, 'shell', $hostWorkspace]);

        if (! $createResult->successful()) {
            File::deleteDirectory($hostWorkspace);
            throw new RuntimeException(
                'docker sandbox create failed: '.trim($createResult->errorOutput() ?: $createResult->output())
            );
        }

        $sandbox = Sandbox::query()->create([
            'project_id' => $task->project_id,
            'task_id' => $task->id,
            'name' => $name,
            'sandbox_id' => $name, // The sandbox name IS the identifier for all exec/rm commands
            'image' => $template,
            'status' => 'active',
            'path' => '/home/agent/workspace',
        ]);

        if (is_string($project?->git_remote_url) && $project->git_remote_url !== '') {
            $cloneResult = $this->exec($sandbox, 'git clone '.$project->git_remote_url.' /home/agent/workspace');

            if (! $cloneResult->successful()) {
                $sandbox->update(['status' => 'failed']);
                throw new RuntimeException(
                    'git clone failed: '.trim($cloneResult->errorOutput() ?: $cloneResult->output())
                );
            }

            $branchResult = $this->exec($sandbox, 'git checkout -b clawra/task-'.$task->id, workingDir: '/home/agent/workspace');

            if (! $branchResult->successful()) {
                $sandbox->update(['status' => 'failed']);
                throw new RuntimeException(
                    'git checkout failed: '.trim($branchResult->errorOutput() ?: $branchResult->output())
                );
            }
        }

        return $sandbox;
    }

    /**
     * Execute a command inside the sandbox.
     *
     * @param  array<string, string>  $env
     */
    public function exec(Sandbox $sandbox, string $command, array $env = [], ?string $workingDir = null): ProcessResult
    {
        $binary = (string) config('services.docker_sandbox.binary', 'docker');
        $timeout = (int) config('services.docker_sandbox.timeout', 600);

        $parts = [$binary, 'sandbox', 'exec'];

        foreach ($env as $key => $value) {
            $parts[] = '--env';
            $parts[] = $key.'='.$value;
        }

        if (is_string($workingDir) && $workingDir !== '') {
            $parts[] = '--workdir';
            $parts[] = $workingDir;
        }

        $parts[] = $sandbox->sandbox_id;
        $parts[] = 'bash';
        $parts[] = '-c';
        $parts[] = $command;

        return Process::timeout($timeout)->run($parts);
    }

    /**
     * Check whether the sandbox container is still running.
     */
    public function isRunning(Sandbox $sandbox): bool
    {
        $binary = (string) config('services.docker_sandbox.binary', 'docker');

        $result = Process::run([$binary, 'sandbox', 'inspect', $sandbox->sandbox_id]);

        return $result->successful();
    }

    /**
     * Remove a sandbox container and clean up its host workspace directory.
     */
    public function remove(Sandbox $sandbox): void
    {
        $binary = (string) config('services.docker_sandbox.binary', 'docker');

        Process::run([$binary, 'sandbox', 'rm', $sandbox->sandbox_id]);

        $hostWorkspace = storage_path('app/sandboxes/'.$sandbox->name);
        File::deleteDirectory($hostWorkspace);

        $sandbox->update(['status' => 'inactive']);
    }

    /**
     * List all running sandboxes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $binary = (string) config('services.docker_sandbox.binary', 'docker');

        $result = Process::run([$binary, 'sandbox', 'ls', '--format', 'json']);

        if (! $result->successful()) {
            return [];
        }

        $decoded = json_decode(trim($result->output()), true);

        return is_array($decoded) ? $decoded : [];
    }
}
