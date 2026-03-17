<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentRuntime;
use App\Models\ExternalSession;
use App\Models\ExternalSessionEvent;
use App\Models\ProcessLog;
use App\Models\Project;
use App\Models\ProjectConversation;
use App\Models\Subtask;
use App\Models\Task;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class ProcessLogService
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function log(
        string $kind,
        string $status,
        string $message,
        array $context = [],
        ?Project $project = null,
        ?ProjectConversation $conversation = null,
        ?Task $task = null,
        ?Subtask $subtask = null,
        ?Agent $agent = null,
        ?AgentRuntime $agentRuntime = null,
        ?ExternalSession $externalSession = null,
    ): ProcessLog {
        return ProcessLog::query()->create([
            'project_id' => $project?->id,
            'project_conversation_id' => $conversation?->id,
            'task_id' => $task?->id,
            'subtask_id' => $subtask?->id,
            'agent_id' => $agent?->id,
            'agent_runtime_id' => $agentRuntime?->id,
            'external_session_id' => $externalSession?->id,
            'kind' => $kind,
            'status' => $status,
            'message' => $message,
            'context' => $context,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function upsertExternalSession(
        string $harness,
        string $externalId,
        string $status = 'active',
        array $metadata = [],
        ?Project $project = null,
        ?ProjectConversation $conversation = null,
        ?Task $task = null,
        ?Subtask $subtask = null,
        ?Agent $agent = null,
        ?AgentRuntime $agentRuntime = null,
        ?string $title = null,
    ): ExternalSession {
        $session = ExternalSession::query()->updateOrCreate(
            [
                'harness' => $harness,
                'external_id' => $externalId,
            ],
            [
                'project_id' => $project?->id,
                'project_conversation_id' => $conversation?->id,
                'task_id' => $task?->id,
                'subtask_id' => $subtask?->id,
                'agent_id' => $agent?->id,
                'agent_runtime_id' => $agentRuntime?->id,
                'status' => $status,
                'title' => $title,
                'metadata' => $metadata,
                'last_seen_at' => now(),
            ],
        );

        return $session->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function logExternalSessionEvent(
        ExternalSession $session,
        string $eventType,
        array $payload = [],
        ?string $externalEventId = null,
        ?CarbonInterface $occurredAt = null,
    ): ExternalSessionEvent {
        $session->forceFill([
            'last_seen_at' => $occurredAt ?? now(),
        ])->save();

        return ExternalSessionEvent::query()->create([
            'external_session_id' => $session->id,
            'event_type' => $eventType,
            'external_event_id' => $externalEventId,
            'payload' => $payload,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    public function latestLogs(int $limit = 30): Collection
    {
        return ProcessLog::query()
            ->with(['project', 'task', 'agent', 'agentRuntime', 'externalSession'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function latestExternalSessions(int $limit = 12): Collection
    {
        return ExternalSession::query()
            ->with(['agent', 'agentRuntime', 'task'])
            ->latest('last_seen_at')
            ->limit($limit)
            ->get();
    }
}
