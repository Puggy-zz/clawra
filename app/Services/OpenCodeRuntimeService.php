<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AgentRuntime;
use Illuminate\Support\Facades\Process;

class OpenCodeRuntimeService
{
    public function __construct(protected ProcessLogService $processLogService) {}

    /**
     * @return array{success: bool, text: string, status: string, command: string, error?: string, external_session?: array<string, mixed>, external_events?: array<int, array<string, mixed>>}
     */
    public function execute(AgentRuntime $runtime, string $prompt): array
    {
        $binary = (string) config('services.opencode.binary', 'opencode');
        $timeout = (int) config('services.opencode.timeout', 120);
        $agent = $runtime->runtime_ref;
        $model = $runtime->model?->external_name ?? $runtime->model?->name;

        $parts = [
            $binary,
            'run',
            '--format',
            'json',
            '--agent',
            $agent,
        ];

        if (is_string($model) && $model !== '') {
            $parts[] = '--model';
            $parts[] = $model;
        }

        $parts[] = $prompt;

        $command = collect($parts)
            ->map(static fn (string $part): string => str_contains($part, ' ') ? '"'.str_replace('"', '\\"', $part).'"' : $part)
            ->implode(' ');

        $result = Process::path(base_path())
            ->timeout($timeout)
            ->run($command);

        if ($result->successful()) {
            $decoded = json_decode($result->output(), true);
            $text = is_array($decoded)
                ? (string) ($decoded['text'] ?? $decoded['output'] ?? $decoded['response'] ?? $result->output())
                : trim($result->output());

            $externalSession = $this->extractExternalSession($decoded, $runtime);
            $externalEvents = $this->extractExternalEvents($decoded);

            return [
                'success' => true,
                'text' => $text,
                'status' => 'completed',
                'command' => $command,
                'external_session' => $externalSession,
                'external_events' => $externalEvents,
            ];
        }

        $decoded = json_decode($result->output(), true);

        return [
            'success' => false,
            'text' => '',
            'status' => 'failed',
            'command' => $command,
            'error' => trim($result->errorOutput()) !== '' ? trim($result->errorOutput()) : trim($result->output()),
            'external_session' => $this->extractExternalSession($decoded, $runtime),
            'external_events' => $this->extractExternalEvents($decoded),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $decoded
     * @return array<string, mixed>|null
     */
    protected function extractExternalSession(?array $decoded, AgentRuntime $runtime): ?array
    {
        if (! is_array($decoded)) {
            return null;
        }

        $sessionId = $decoded['session']['id']
            ?? $decoded['sessionID']
            ?? $decoded['session_id']
            ?? null;

        if (! is_string($sessionId) || $sessionId === '') {
            return null;
        }

        return [
            'harness' => 'opencode',
            'external_id' => $sessionId,
            'status' => (string) ($decoded['status'] ?? 'active'),
            'title' => (string) ($decoded['session']['title'] ?? $runtime->name),
            'metadata' => [
                'message_id' => $decoded['messageID'] ?? $decoded['message_id'] ?? $decoded['message']['id'] ?? null,
                'part_id' => $decoded['partID'] ?? $decoded['part_id'] ?? null,
                'command' => $decoded['command'] ?? null,
                'raw' => $decoded,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $decoded
     * @return array<int, array<string, mixed>>
     */
    protected function extractExternalEvents(?array $decoded): array
    {
        if (! is_array($decoded)) {
            return [];
        }

        $events = [];

        if (isset($decoded['events']) && is_array($decoded['events'])) {
            foreach ($decoded['events'] as $event) {
                if (is_array($event)) {
                    $events[] = [
                        'event_type' => (string) ($event['type'] ?? $event['event'] ?? 'unknown'),
                        'external_event_id' => $event['id'] ?? null,
                        'payload' => $event,
                    ];
                }
            }
        }

        if ($events === [] && isset($decoded['session']) && is_array($decoded['session'])) {
            $events[] = [
                'event_type' => 'session.completed',
                'external_event_id' => $decoded['session']['id'] ?? null,
                'payload' => $decoded,
            ];
        }

        return $events;
    }
}
