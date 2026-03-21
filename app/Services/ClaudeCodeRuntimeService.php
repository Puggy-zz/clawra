<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AgentRuntime;
use Illuminate\Support\Facades\Process;

class ClaudeCodeRuntimeService
{
    public function __construct(protected ProcessLogService $processLogService) {}

    /**
     * @return array{success: bool, text: string, status: string, command: string, error?: string, external_session?: array<string, mixed>, external_events?: array<int, array<string, mixed>>}
     */
    public function execute(AgentRuntime $runtime, string $prompt, ?string $workspacePath = null): array
    {
        $nodeBinary = (string) config('services.claude_code.node_binary', 'node');
        $timeout = (int) config('services.claude_code.timeout', 300);
        $model = $runtime->model?->external_name ?? $runtime->model?->name;

        $parts = [
            $nodeBinary,
            'scripts/agents/claude-code-runner.mjs',
        ];

        if (is_string($model) && $model !== '') {
            $parts[] = '--model';
            $parts[] = $model;
        }

        $runPath = (is_string($workspacePath) && $workspacePath !== '' && is_dir($workspacePath))
            ? $workspacePath
            : base_path();

        $parts[] = '--cwd';
        $parts[] = $runPath;

        $command = collect($parts)
            ->map(static fn (string $part): string => str_contains($part, ' ') ? '"'.str_replace('"', '\\"', $part).'"' : $part)
            ->implode(' ');

        $result = Process::path(base_path())
            ->timeout($timeout)
            ->env(['CLAWRA_PROMPT' => $prompt])
            ->run($command);

        $output = trim($result->output());
        $decoded = json_decode($output, true);

        if (! is_array($decoded)) {
            return [
                'success' => false,
                'text' => '',
                'status' => 'failed',
                'command' => $command,
                'error' => 'Malformed JSON output from claude-code-runner: '.($output !== '' ? $output : trim($result->errorOutput())),
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

        if ($result->successful() && ($decoded['success'] ?? false) === true) {
            return [
                'success' => true,
                'text' => (string) ($decoded['result'] ?? ''),
                'status' => 'completed',
                'command' => $command,
                'external_session' => $externalSession,
                'external_events' => $this->buildExternalEvents($sessionId),
            ];
        }

        return [
            'success' => false,
            'text' => '',
            'status' => 'failed',
            'command' => $command,
            'error' => (string) ($decoded['error'] ?? trim($result->errorOutput()) ?: 'Claude Code runner returned failure.'),
            'external_session' => $externalSession,
            'external_events' => $this->buildExternalEvents($sessionId),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildExternalEvents(?string $sessionId): array
    {
        if ($sessionId === null) {
            return [];
        }

        return [[
            'event_type' => 'session.completed',
            'external_event_id' => $sessionId,
            'payload' => [],
        ]];
    }
}
