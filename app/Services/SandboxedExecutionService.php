<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AgentRuntime;
use App\Models\Sandbox;
use App\Models\Task;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class SandboxedExecutionService
{
    public function __construct(
        protected SandboxManagerService $sandboxManager,
        protected ProcessLogService $processLogService,
    ) {}

    /**
     * Execute a prompt inside a Docker sandbox using the harness configured
     * in $runtime->config['inner_harness'] (defaults to 'opencode').
     *
     * @return array{success: bool, text: string, status: string, command: string, error?: string, external_session?: array<string, mixed>|null, external_events?: array<int, array<string, mixed>>}
     */
    public function execute(AgentRuntime $runtime, string $prompt, ?string $workspacePath = null, ?Task $task = null): array
    {
        if (! $task instanceof Task) {
            throw new RuntimeException('SandboxedExecutionService requires a Task instance.');
        }

        $sandbox = Sandbox::query()
            ->where('task_id', $task->id)
            ->where('status', 'active')
            ->first();

        if ($sandbox instanceof Sandbox) {
            $this->processLogService->log(
                kind: 'sandbox.reused',
                status: 'info',
                message: sprintf('Reusing existing sandbox [%s].', $sandbox->sandbox_id),
                context: ['sandbox_id' => $sandbox->sandbox_id],
                task: $task,
                agentRuntime: $runtime,
            );
        } else {
            $this->processLogService->log(
                kind: 'sandbox.provisioning',
                status: 'started',
                message: 'No active sandbox found — provisioning a new one.',
                task: $task,
                agentRuntime: $runtime,
            );

            try {
                $sandbox = $this->sandboxManager->provisionForTask($task);
            } catch (\Throwable $e) {
                $this->processLogService->log(
                    kind: 'sandbox.provisioning',
                    status: 'failed',
                    message: 'Sandbox provisioning failed: '.$e->getMessage(),
                    task: $task,
                    agentRuntime: $runtime,
                );

                throw $e;
            }

            $this->processLogService->log(
                kind: 'sandbox.provisioning',
                status: 'completed',
                message: sprintf('Sandbox [%s] provisioned.', $sandbox->sandbox_id),
                context: ['sandbox_id' => $sandbox->sandbox_id, 'image' => $sandbox->image],
                task: $task,
                agentRuntime: $runtime,
            );
        }

        // claude_code runs on the host via the Agent SDK (OAuth) with --cwd pointing
        // at the sandbox's host-side workspace mount, so credentials never enter the container.
        if ($runtime->harness === 'claude_code') {
            return $this->executeClaudeCodeOnHost($runtime, $prompt, $sandbox, $task);
        }

        [$command, $env] = match ($runtime->harness) {
            'codex' => $this->buildCodexCommand($runtime, $prompt),
            default => $this->buildOpenCodeCommand($runtime, $prompt),
        };

        $this->processLogService->log(
            kind: 'sandbox.exec',
            status: 'started',
            message: sprintf('Executing [%s] command in sandbox [%s].', $runtime->harness, $sandbox->sandbox_id),
            context: ['command' => $command],
            task: $task,
            agentRuntime: $runtime,
        );

        $result = $this->sandboxManager->exec(
            $sandbox,
            $command,
            env: array_merge($runtime->config['env'] ?? [], $env),
            workingDir: '/home/agent/workspace',
        );

        $exitCode = $result->exitCode();

        if (in_array($exitCode, [125, 126], true)) {
            $sandbox->update(['status' => 'failed']);

            $this->processLogService->log(
                kind: 'sandbox.exec',
                status: 'failed',
                message: sprintf('Sandbox [%s] marked failed due to exit code %d.', $sandbox->sandbox_id, $exitCode),
                context: ['exit_code' => $exitCode, 'error_output' => $result->errorOutput()],
                task: $task,
                agentRuntime: $runtime,
            );
        } else {
            $rawOutput = $result->output();
            $this->processLogService->log(
                kind: 'sandbox.exec',
                status: $exitCode === 0 ? 'completed' : 'failed',
                message: sprintf('Sandbox exec finished with exit code %d.', $exitCode),
                context: [
                    'exit_code' => $exitCode,
                    'output_preview' => mb_substr($rawOutput, 0, 1000),
                    'error_output' => $result->errorOutput() !== '' ? $result->errorOutput() : null,
                ],
                task: $task,
                agentRuntime: $runtime,
            );
        }

        $output = match ($runtime->harness) {
            'codex' => $this->parseCodexOutput($result->output(), $command),
            default => $this->parseOpenCodeOutput($result->output(), $result, $command, $runtime),
        };

        return $output;
    }

    /**
     * Run claude-code-runner.mjs on the host (OAuth via Agent SDK) with --cwd pointing
     * at the sandbox's host-side workspace mount. This avoids injecting credentials into
     * the container while still scoping file changes to the sandbox workspace.
     *
     * @return array{success: bool, text: string, status: string, command: string, error?: string, external_session?: array<string, mixed>|null, external_events?: array<int, array<string, mixed>>}
     */
    protected function executeClaudeCodeOnHost(AgentRuntime $runtime, string $prompt, Sandbox $sandbox, Task $task): array
    {
        $nodeBinary = (string) config('services.claude_code.node_binary', 'node');
        $timeout = (int) config('services.claude_code.timeout', 300);
        $model = $runtime->model?->external_name ?? $runtime->model?->name;

        $hostWorkspace = storage_path('app/sandboxes/'.$sandbox->name);

        $parts = [$nodeBinary, 'scripts/agents/claude-code-runner.mjs', '--cwd', $hostWorkspace];

        if (is_string($model) && $model !== '') {
            $parts[] = '--model';
            $parts[] = $model;
        }

        $command = collect($parts)
            ->map(static fn (string $p): string => str_contains($p, ' ') ? '"'.str_replace('"', '\\"', $p).'"' : $p)
            ->implode(' ');

        $this->processLogService->log(
            kind: 'sandbox.exec',
            status: 'started',
            message: sprintf('Executing [claude_code] via Agent SDK on host for sandbox [%s].', $sandbox->sandbox_id),
            context: ['command' => $command, 'cwd' => $hostWorkspace],
            task: $task,
            agentRuntime: $runtime,
        );

        $result = Process::path(base_path())
            ->timeout($timeout)
            ->env(['CLAWRA_PROMPT' => $prompt])
            ->run($command);

        $exitCode = $result->exitCode();
        $rawOutput = trim($result->output());
        $decoded = json_decode($rawOutput, true);

        $this->processLogService->log(
            kind: 'sandbox.exec',
            status: $exitCode === 0 ? 'completed' : 'failed',
            message: sprintf('Agent SDK exec finished with exit code %d.', $exitCode),
            context: [
                'exit_code' => $exitCode,
                'output_preview' => mb_substr($rawOutput, 0, 1000),
                'error_output' => $result->errorOutput() !== '' ? $result->errorOutput() : null,
            ],
            task: $task,
            agentRuntime: $runtime,
        );

        if (! is_array($decoded)) {
            return [
                'success' => false,
                'text' => '',
                'status' => 'failed',
                'command' => $command,
                'error' => 'Malformed JSON from claude-code-runner: '.($rawOutput !== '' ? $rawOutput : trim($result->errorOutput())),
            ];
        }

        $sessionId = is_string($decoded['session_id'] ?? null) ? $decoded['session_id'] : null;
        $externalSession = $sessionId !== null ? [
            'harness' => 'claude_code',
            'external_id' => $sessionId,
            'status' => 'completed',
            'title' => $runtime->name,
            'metadata' => ['stop_reason' => $decoded['stop_reason'] ?? 'end_turn'],
        ] : null;
        $externalEvents = $sessionId !== null ? [[
            'event_type' => 'session.completed',
            'external_event_id' => $sessionId,
            'payload' => [],
        ]] : [];

        if (($decoded['success'] ?? false) === true) {
            return [
                'success' => true,
                'text' => (string) ($decoded['result'] ?? ''),
                'status' => 'completed',
                'command' => $command,
                'external_session' => $externalSession,
                'external_events' => $externalEvents,
            ];
        }

        return [
            'success' => false,
            'text' => '',
            'status' => 'failed',
            'command' => $command,
            'error' => (string) ($decoded['error'] ?? trim($result->errorOutput()) ?: 'claude-code-runner returned failure.'),
            'external_session' => $externalSession,
            'external_events' => $externalEvents,
        ];
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    protected function buildOpenCodeCommand(AgentRuntime $runtime, string $prompt): array
    {
        $agent = (string) ($runtime->config['opencode_agent'] ?? $runtime->runtime_ref ?? '');
        $model = $runtime->model?->external_name ?? $runtime->model?->name;

        $parts = ['opencode', 'run', '--format', 'json'];

        if ($agent !== '') {
            $parts[] = '--agent';
            $parts[] = $agent;
        }

        if (is_string($model) && $model !== '') {
            $parts[] = '--model';
            $parts[] = $model;
        }

        $escapedPrompt = str_contains($prompt, ' ') ? '"'.str_replace('"', '\\"', $prompt).'"' : $prompt;
        $parts[] = $escapedPrompt;

        return [implode(' ', $parts), []];
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    protected function buildClaudeCodeCommand(AgentRuntime $runtime, string $prompt): array
    {
        $model = $runtime->model?->external_name ?? $runtime->model?->name;

        // Inside the sandbox, `claude` is installed as a CLI binary. Use
        // --print for non-interactive mode and --output-format json for
        // structured output. --dangerously-skip-permissions prevents interactive
        // permission prompts that would hang the non-interactive run.
        $parts = ['claude', '--print', '--output-format', 'json', '--dangerously-skip-permissions'];

        if (is_string($model) && $model !== '') {
            $parts[] = '--model';
            $parts[] = $model;
        }

        $escapedPrompt = str_contains($prompt, ' ') ? '"'.str_replace('"', '\\"', $prompt).'"' : $prompt;
        $parts[] = $escapedPrompt;

        return [implode(' ', $parts), []];
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    protected function buildCodexCommand(AgentRuntime $runtime, string $prompt): array
    {
        $model = $runtime->model?->external_name ?? $runtime->model?->name;

        $parts = ['codex'];

        if (is_string($model) && $model !== '') {
            $parts[] = '--model';
            $parts[] = $model;
        }

        $escapedPrompt = str_contains($prompt, ' ') ? '"'.str_replace('"', '\\"', $prompt).'"' : $prompt;
        $parts[] = $escapedPrompt;

        return [implode(' ', $parts), []];
    }

    /**
     * Parse NDJSON output from opencode.
     *
     * @return array{success: bool, text: string, status: string, command: string, error?: string, external_session?: array<string, mixed>|null, external_events?: array<int, array<string, mixed>>}
     */
    protected function parseOpenCodeOutput(string $output, mixed $result, string $command, AgentRuntime $runtime): array
    {
        $events = [];

        foreach (explode("\n", trim($output)) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $events[] = $decoded;
            }
        }

        $errorEvent = null;
        foreach ($events as $event) {
            if (($event['type'] ?? null) === 'error' && isset($event['error'])) {
                $errorEvent = $event;
                break;
            }
        }

        $isSuccessful = method_exists($result, 'successful') ? $result->successful() : true;

        if ($isSuccessful && $errorEvent === null) {
            return [
                'success' => true,
                'text' => $this->extractOpenCodeText($events, $output),
                'status' => 'completed',
                'command' => $command,
                'external_session' => $this->extractOpenCodeSession($events, $runtime),
                'external_events' => $this->buildOpenCodeEvents($events),
            ];
        }

        $errorMessage = $errorEvent !== null
            ? (string) ($errorEvent['error']['data']['message'] ?? $errorEvent['error']['name'] ?? json_encode($errorEvent['error']))
            : trim($output);

        return [
            'success' => false,
            'text' => '',
            'status' => 'failed',
            'command' => $command,
            'error' => $errorMessage,
            'external_session' => $this->extractOpenCodeSession($events, $runtime),
            'external_events' => $this->buildOpenCodeEvents($events),
        ];
    }

    /**
     * Parse JSON output from claude-code-runner.
     *
     * @return array{success: bool, text: string, status: string, command: string, error?: string, external_session?: array<string, mixed>|null, external_events?: array<int, array<string, mixed>>}
     */
    protected function parseClaudeCodeOutput(string $rawOutput, string $command, AgentRuntime $runtime): array
    {
        $output = trim($rawOutput);
        $decoded = json_decode($output, true);

        if (! is_array($decoded)) {
            return [
                'success' => false,
                'text' => '',
                'status' => 'failed',
                'command' => $command,
                'error' => 'Malformed JSON output from claude-code-runner: '.($output !== '' ? $output : 'empty output'),
            ];
        }

        $sessionId = is_string($decoded['session_id'] ?? null) ? $decoded['session_id'] : null;
        $externalSession = $sessionId !== null ? [
            'harness' => 'claude_code',
            'external_id' => $sessionId,
            'status' => 'completed',
            'title' => $runtime->name,
            'metadata' => ['stop_reason' => $decoded['stop_reason'] ?? 'end_turn'],
        ] : null;

        $externalEvents = $sessionId !== null ? [[
            'event_type' => 'session.completed',
            'external_event_id' => $sessionId,
            'payload' => [],
        ]] : [];

        // Support both custom runner format {success: bool} and claude CLI
        // JSON format {is_error: bool} — both use the same 'result' key.
        $isSuccess = ($decoded['success'] ?? null) === true
            || ($decoded['is_error'] ?? null) === false;

        if ($isSuccess) {
            return [
                'success' => true,
                'text' => (string) ($decoded['result'] ?? ''),
                'status' => 'completed',
                'command' => $command,
                'external_session' => $externalSession,
                'external_events' => $externalEvents,
            ];
        }

        return [
            'success' => false,
            'text' => '',
            'status' => 'failed',
            'command' => $command,
            'error' => (string) ($decoded['error'] ?? 'Claude Code runner returned failure.'),
            'external_session' => $externalSession,
            'external_events' => $externalEvents,
        ];
    }

    /**
     * Parse plain-text output from codex.
     *
     * @return array{success: bool, text: string, status: string, command: string, error?: string}
     */
    protected function parseCodexOutput(string $rawOutput, string $command): array
    {
        $text = trim($rawOutput);

        return [
            'success' => $text !== '',
            'text' => $text,
            'status' => $text !== '' ? 'completed' : 'failed',
            'command' => $command,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     */
    private function extractOpenCodeText(array $events, string $rawOutput): string
    {
        $parts = [];

        foreach ($events as $event) {
            $type = $event['type'] ?? $event['part']['type'] ?? null;

            if ($type === 'text' || $type === 'step_finish') {
                $text = $event['part']['text'] ?? $event['text'] ?? null;
                if (is_string($text) && $text !== '') {
                    $parts[] = $text;
                }
            }
        }

        if ($parts !== []) {
            return implode('', $parts);
        }

        foreach (array_reverse($events) as $event) {
            if (($event['type'] ?? null) === 'step_finish') {
                $snapshot = $event['part']['snapshot'] ?? null;
                if (is_string($snapshot) && $snapshot !== '') {
                    return $snapshot;
                }
            }
        }

        return $rawOutput;
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @return array<string, mixed>|null
     */
    private function extractOpenCodeSession(array $events, AgentRuntime $runtime): ?array
    {
        $sessionId = null;
        $title = null;

        foreach ($events as $event) {
            if (! is_string($sessionId) || $sessionId === '') {
                $sessionId = $event['sessionID'] ?? $event['session_id'] ?? $event['session']['id'] ?? null;
            }
            if (! is_string($title) || $title === '') {
                $title = $event['session']['title'] ?? null;
            }
        }

        if (! is_string($sessionId) || $sessionId === '') {
            return null;
        }

        return [
            'harness' => 'opencode',
            'external_id' => $sessionId,
            'status' => 'completed',
            'title' => $title ?? $runtime->name,
            'metadata' => ['event_count' => count($events)],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     * @return array<int, array<string, mixed>>
     */
    private function buildOpenCodeEvents(array $events): array
    {
        if ($events === []) {
            return [];
        }

        $sessionId = null;
        foreach ($events as $event) {
            $sessionId = $event['sessionID'] ?? $event['session']['id'] ?? null;
            if ($sessionId !== null) {
                break;
            }
        }

        return [[
            'event_type' => 'session.completed',
            'external_event_id' => $sessionId,
            'payload' => ['event_count' => count($events)],
        ]];
    }
}
