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
    public function execute(AgentRuntime $runtime, string $prompt, ?string $workspacePath = null): array
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

        $runPath = (is_string($workspacePath) && $workspacePath !== '' && is_dir($workspacePath))
            ? $workspacePath
            : base_path();

        $result = Process::path($runPath)
            ->timeout($timeout)
            ->run($command);

        $events = $this->parseNdjson($result->output());
        $errorEvent = $this->findErrorEvent($events);

        if ($result->successful() && $errorEvent === null) {
            $text = $this->extractText($events, $result->output());

            return [
                'success' => true,
                'text' => $text,
                'status' => 'completed',
                'command' => $command,
                'external_session' => $this->extractExternalSession($events, $runtime),
                'external_events' => $this->extractExternalEvents($events),
            ];
        }

        $errorMessage = $errorEvent !== null
            ? (string) ($errorEvent['error']['data']['message'] ?? $errorEvent['error']['name'] ?? json_encode($errorEvent['error']))
            : (trim($result->errorOutput()) !== '' ? trim($result->errorOutput()) : trim($result->output()));

        return [
            'success' => false,
            'text' => '',
            'status' => 'failed',
            'command' => $command,
            'error' => $errorMessage,
            'external_session' => $this->extractExternalSession($events, $runtime),
            'external_events' => $this->extractExternalEvents($events),
        ];
    }

    /**
     * Parse NDJSON output (one JSON object per line) into an array of decoded events.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function parseNdjson(string $output): array
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

        return $events;
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     */
    protected function findErrorEvent(array $events): ?array
    {
        foreach ($events as $event) {
            if (($event['type'] ?? null) === 'error' && isset($event['error'])) {
                return $event;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $events
     */
    protected function extractText(array $events, string $rawOutput): string
    {
        // Collect all assistant text parts
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

        // Fallback: last snapshot from a step_finish event
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
    protected function extractExternalSession(array $events, AgentRuntime $runtime): ?array
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
    protected function extractExternalEvents(array $events): array
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
