<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Agents\ReviewerAgent;
use App\Models\Document;
use App\Models\Sandbox;
use App\Models\Task;
use App\Services\ProcessLogService;
use App\Services\RuntimeExecutionService;
use App\Services\SandboxManagerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class ExecuteTaskJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(public readonly int $taskId)
    {
        $this->onQueue('low');
    }

    public function handle(RuntimeExecutionService $executor, ProcessLogService $log, ReviewerAgent $reviewer, SandboxManagerService $sandboxManager): void
    {
        $task = Task::query()
            ->with(['project', 'recommendedAgent.defaultRuntime'])
            ->find($this->taskId);

        if (! $task instanceof Task) {
            return;
        }

        if ($task->status !== 'in-progress') {
            return;
        }

        $agentName = $this->resolveAgentName($task);
        $prompt = sprintf("Task: %s\n\n%s\n\nProject: %s", $task->name, $task->description ?? '', $task->project?->name ?? 'Unknown');
        $workspacePath = $task->project?->workspace_path;

        try {
            $result = $executor->executeAgent($agentName, $prompt, $workspacePath, $task);

            $finalStatus = $result['success'] ? 'completed' : 'failed';

            if ($result['success'] && ($result['harness'] ?? null) === 'opencode' && ! empty($result['text'])) {
                $review = $reviewer->reviewTaskCompletion(
                    $task->name,
                    $task->description ?? '',
                    $result['text'],
                );

                $result['review'] = $review;

                if ($review['decision'] !== 'completed') {
                    $finalStatus = 'failed';
                }
            }

            $task->update([
                'status' => $finalStatus,
                'result' => $result['text'] ?? null,
            ]);

            $savesDocuments = (bool) ($task->recommendedAgent?->defaultRuntime?->saves_documents);

            if ($result['success'] && ! empty($result['text']) && $savesDocuments && is_string($workspacePath) && $workspacePath !== '') {
                $this->saveResultDocument($task, $result['text'], $workspacePath);
            }

            $log->log(
                kind: 'task.execution.'.($finalStatus === 'completed' ? 'completed' : 'failed'),
                status: $finalStatus,
                message: $finalStatus === 'completed'
                    ? sprintf('Task [%s] completed via %s.', $task->name, $agentName)
                    : sprintf('Task [%s] failed via %s: %s', $task->name, $agentName, $result['error'] ?? ($result['review']['reasoning'] ?? 'unknown error')),
                context: [
                    'agent' => $agentName,
                    'text' => $result['text'] ?? '',
                    'error' => $result['error'] ?? null,
                    'review' => $result['review'] ?? null,
                ],
                task: $task,
                agent: $task->recommendedAgent,
            );
        } catch (Throwable $e) {
            $task->update([
                'status' => 'failed',
                'result' => $e->getMessage(),
            ]);

            $log->log(
                kind: 'task.execution.failed',
                status: 'failed',
                message: sprintf('Task [%s] failed with exception: %s', $task->name, $e->getMessage()),
                context: ['agent' => $agentName, 'exception' => $e->getMessage()],
                task: $task,
                agent: $task->recommendedAgent,
            );
        } finally {
            $this->cleanupSandbox($task, $sandboxManager);
        }
    }

    protected function cleanupSandbox(Task $task, SandboxManagerService $sandboxManager): void
    {
        try {
            $sandbox = Sandbox::query()
                ->where('task_id', $task->id)
                ->whereIn('status', ['active', 'failed'])
                ->first();

            if ($sandbox instanceof Sandbox) {
                $sandboxManager->remove($sandbox);
            }
        } catch (Throwable) {
            // Non-fatal — sandbox cleanup failure must not obscure task result
        }
    }

    protected function saveResultDocument(Task $task, string $content, string $workspacePath): void
    {
        try {
            $docsDir = rtrim($workspacePath, '/\\').DIRECTORY_SEPARATOR.'documents';
            File::ensureDirectoryExists($docsDir);

            $slug = Str::slug($task->name);
            $timestamp = now()->format('Y-m-d_His');
            $fileName = "{$slug}-{$timestamp}.md";
            $filePath = $docsDir.DIRECTORY_SEPARATOR.$fileName;

            File::put($filePath, "# {$task->name}\n\n{$content}");

            Document::query()->create([
                'title' => $task->name,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'content' => $content,
                'file_type' => 'md',
                'project_id' => $task->project_id,
                'task_id' => $task->id,
                'access_level' => 'internal',
            ]);
        } catch (Throwable) {
            // Non-fatal — document saving failure must not mark the task as failed
        }
    }

    protected function resolveAgentName(Task $task): string
    {
        if ($task->recommendedAgent?->name) {
            return $task->recommendedAgent->name;
        }

        $text = strtolower(implode(' ', array_filter([$task->name, $task->description])));

        if (str_contains($text, 'research') || str_contains($text, 'investigate')) {
            return 'Researcher';
        }

        if (str_contains($text, 'plan') || str_contains($text, 'initialize') || str_contains($text, 'finalize')) {
            return 'Planner';
        }

        return 'Planner';
    }
}
