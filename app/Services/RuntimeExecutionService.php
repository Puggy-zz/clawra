<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentRuntime;
use App\Models\ExternalSession;
use App\Models\Task;

class RuntimeExecutionService
{
    public function __construct(
        protected AgentService $agentService,
        protected AiService $aiService,
        protected OpenCodeRuntimeService $openCodeRuntimeService,
        protected ClaudeCodeRuntimeService $claudeCodeRuntimeService,
        protected SandboxedExecutionService $sandboxedExecutionService,
        protected ProcessLogService $processLogService,
    ) {}

    /**
     * @return array{success: bool, text: string, status: string, harness?: string, runtime?: string, error?: string}
     */
    public function executeAgent(string $agentName, string $prompt, ?string $workspacePath = null, ?Task $task = null): array
    {
        $runtime = $this->agentService->getPreferredRuntimeForAgent($agentName);
        $agent = $this->agentService->getAgentByName($agentName);

        if (! $runtime instanceof AgentRuntime) {
            $this->processLogService->log(
                kind: 'runtime.execution.missing',
                status: 'failed',
                message: sprintf('No active runtime configured for [%s].', $agentName),
                context: ['prompt' => $prompt],
                agent: $agent,
                task: $task,
            );

            return [
                'success' => false,
                'text' => '',
                'status' => 'failed',
                'error' => sprintf('No active runtime configured for [%s].', $agentName),
            ];
        }

        return $this->executeRuntime($runtime, $prompt, $agent, $workspacePath, $task);
    }

    /**
     * @return array{success: bool, text: string, status: string, harness?: string, runtime?: string, error?: string}
     */
    public function executeRuntime(AgentRuntime $runtime, string $prompt, ?Agent $agent = null, ?string $workspacePath = null, ?Task $task = null): array
    {
        $this->processLogService->log(
            kind: 'runtime.execution.started',
            status: 'started',
            message: sprintf('Starting %s runtime [%s].', $runtime->harness, $runtime->name),
            context: [
                'prompt' => $prompt,
                'runtime_ref' => $runtime->runtime_ref,
                'provider_route' => $runtime->route?->name,
                'provider_model' => $runtime->model?->name,
            ],
            agent: $agent,
            agentRuntime: $runtime,
            task: $task,
        );

        if ($runtime->sandboxed) {
            $response = $this->sandboxedExecutionService->execute($runtime, $prompt, $workspacePath, $task);
        } else {
            $response = match ($runtime->harness) {
                'opencode' => $this->openCodeRuntimeService->execute($runtime, $prompt, $workspacePath),
                'claude_code' => $this->claudeCodeRuntimeService->execute($runtime, $prompt, $workspacePath),
                'laravel_ai' => $this->executeLaravelAiRuntime($runtime, $prompt),
                default => [
                    'success' => false,
                    'text' => '',
                    'status' => 'failed',
                    'error' => sprintf('Harness [%s] is not implemented yet.', $runtime->harness),
                ],
            };
        }

        $response['harness'] = $runtime->harness;
        $response['runtime'] = $runtime->name;

        $externalSession = $this->persistExternalSessionArtifacts($response, $runtime, $agent, $task);

        if ($externalSession instanceof ExternalSession) {
            $response['external_session_id'] = $externalSession->id;
            $response['external_session_ref'] = $externalSession->external_id;
        }

        $this->processLogService->log(
            kind: 'runtime.execution.'.($response['success'] ? 'completed' : 'failed'),
            status: $response['status'],
            message: $response['success'] ? 'Runtime execution completed.' : 'Runtime execution failed.',
            context: [
                'text' => $response['text'] ?? '',
                'error' => $response['error'] ?? null,
                'provider_route' => $runtime->route?->name,
                'provider_model' => $runtime->model?->name,
            ],
            agent: $agent,
            agentRuntime: $runtime,
            externalSession: $externalSession,
            task: $task,
        );

        if (! $response['success'] && in_array($runtime->fallbackRoute?->harness, ['opencode', 'claude_code'], true)) {
            $this->processLogService->log(
                kind: 'runtime.execution.fallback',
                status: 'retrying',
                message: sprintf('Retrying runtime [%s] with fallback route [%s].', $runtime->name, $runtime->fallbackRoute->name),
                context: [
                    'original_route' => $runtime->route?->name,
                    'fallback_route' => $runtime->fallbackRoute->name,
                    'fallback_model' => $runtime->fallbackModel?->name,
                ],
                agent: $agent,
                agentRuntime: $runtime,
                externalSession: $externalSession,
                task: $task,
            );

            $fallbackRuntime = $runtime->replicate(['provider_route_id', 'provider_model_id']);
            $fallbackRuntime->provider_route_id = $runtime->fallback_provider_route_id;
            $fallbackRuntime->provider_model_id = $runtime->fallback_provider_model_id;
            $fallbackRuntime->setRelation('route', $runtime->fallbackRoute);
            $fallbackRuntime->setRelation('model', $runtime->fallbackModel);

            $fallbackResponse = match ($runtime->fallbackRoute->harness) {
                'claude_code' => $this->claudeCodeRuntimeService->execute($fallbackRuntime, $prompt, $workspacePath),
                default => $this->openCodeRuntimeService->execute($fallbackRuntime, $prompt, $workspacePath),
            };
            $fallbackResponse['harness'] = $runtime->fallbackRoute->harness;
            $fallbackResponse['runtime'] = $runtime->name.'-fallback';

            $fallbackSession = $this->persistExternalSessionArtifacts($fallbackResponse, $fallbackRuntime, $agent, $task);

            if ($fallbackSession instanceof ExternalSession) {
                $fallbackResponse['external_session_id'] = $fallbackSession->id;
                $fallbackResponse['external_session_ref'] = $fallbackSession->external_id;
            }

            $this->processLogService->log(
                kind: 'runtime.execution.'.($fallbackResponse['success'] ? 'completed' : 'failed'),
                status: $fallbackResponse['status'],
                message: $fallbackResponse['success'] ? 'Fallback runtime execution completed.' : 'Fallback runtime execution failed.',
                context: [
                    'text' => $fallbackResponse['text'] ?? '',
                    'error' => $fallbackResponse['error'] ?? null,
                    'provider_route' => $fallbackRuntime->route?->name,
                    'provider_model' => $fallbackRuntime->model?->name,
                ],
                agent: $agent,
                agentRuntime: $runtime,
                externalSession: $fallbackSession,
                task: $task,
            );

            return $fallbackResponse;
        }

        return $response;
    }

    /**
     * @return array{success: bool, text: string, status: string, error?: string}
     */
    protected function executeLaravelAiRuntime(AgentRuntime $runtime, string $prompt): array
    {
        $primary = $runtime->model?->external_name ?? $runtime->model?->name ?? $runtime->route?->provider?->name;
        $fallback = $runtime->fallbackModel?->external_name ?? $runtime->fallbackModel?->name ?? $runtime->fallbackRoute?->provider?->name;

        if (! is_string($primary) || $primary === '') {
            return [
                'success' => false,
                'text' => '',
                'status' => 'failed',
                'error' => 'Laravel AI runtime is missing a primary model or provider.',
            ];
        }

        $response = is_string($fallback) && $fallback !== ''
            ? $this->aiService->promptWithFallback($prompt, $primary, $fallback)
            : $this->aiService->prompt($prompt, $primary);

        return [
            'success' => (bool) ($response['success'] ?? false),
            'text' => (string) ($response['text'] ?? ''),
            'status' => ($response['success'] ?? false) ? 'completed' : 'failed',
            'error' => $response['error'] ?? null,
        ];
    }

    protected function persistExternalSessionArtifacts(array $response, AgentRuntime $runtime, ?Agent $agent = null, ?Task $task = null): ?ExternalSession
    {
        $sessionPayload = $response['external_session'] ?? null;

        if (! is_array($sessionPayload) || ! is_string($sessionPayload['external_id'] ?? null)) {
            return null;
        }

        $session = $this->processLogService->upsertExternalSession(
            harness: $sessionPayload['harness'] ?? $runtime->harness,
            externalId: $sessionPayload['external_id'],
            status: $sessionPayload['status'] ?? 'active',
            metadata: $sessionPayload['metadata'] ?? [],
            agent: $agent,
            agentRuntime: $runtime,
            title: $sessionPayload['title'] ?? $runtime->name,
            task: $task,
        );

        foreach ($response['external_events'] ?? [] as $event) {
            if (is_array($event)) {
                $this->processLogService->logExternalSessionEvent(
                    $session,
                    (string) ($event['event_type'] ?? 'unknown'),
                    $event['payload'] ?? [],
                    $event['external_event_id'] ?? null,
                );
            }
        }

        return $session;
    }
}
