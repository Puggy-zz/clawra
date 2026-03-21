<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Agents\CoordinatorAgent;
use App\Jobs\RunHeartbeatJob;
use App\Models\AgentRuntime;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use App\Services\AgentService;
use App\Services\HeartbeatScheduler;
use App\Services\ProcessLogService;
use App\Services\ProjectConversationService;
use App\Services\ProjectService;
use App\Services\ProviderService;
use App\Services\TaskService;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CoordinatorController extends Controller
{
    public function __construct(
        protected CoordinatorAgent $coordinatorAgent,
        protected AgentService $agentService,
        protected ProjectConversationService $projectConversationService,
        protected ProjectService $projectService,
        protected ProcessLogService $processLogService,
        protected TaskService $taskService,
        protected WorkflowService $workflowService,
        protected ProviderService $providerService,
        protected HeartbeatScheduler $heartbeatScheduler,
    ) {}

    public function index(): View
    {
        $projectId = request()->integer('project_id') ?: null;
        $conversationId = request()->integer('conversation_id') ?: null;
        ['project' => $activeProject, 'conversation' => $activeConversation, 'conversations' => $projectConversations] = $this->projectConversationService->resolveContext($projectId, $conversationId);

        return view('coordinator', [
            'agents' => $this->agentService->getAllAgents(),
            'assignableAgents' => $this->agentService->getAssignableAgents(),
            'projects' => $this->projectService->getAllProjects(),
            'activeProject' => $activeProject,
            'activeConversation' => $activeConversation,
            'projectConversations' => $projectConversations,
            'conversationMessages' => $this->projectConversationService->getMessages($activeConversation),
            'tasks' => $this->taskService->getAllTasks(),
            'workflows' => $this->workflowService->getAllWorkflows(),
            'providers' => $this->providerService->getAllProviders(),
            'providerRoutes' => $this->providerService->getAllRoutes(),
            'providerModels' => ProviderModel::query()->with('route.provider')->orderBy('name')->get(),
            'pendingTaskDraft' => $this->projectConversationService->getPendingTaskDraft($activeConversation),
            'processLogs' => $this->processLogService->latestLogs(),
            'externalSessions' => $this->processLogService->latestExternalSessions(),
            'heartbeatLogs' => \App\Models\HeartbeatLog::query()->latest('timestamp')->limit(5)->get(),
        ]);
    }

    public function processMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'project_id' => 'nullable|exists:projects,id',
            'conversation_id' => 'nullable|exists:project_conversations,id',
        ]);

        $result = $this->coordinatorAgent->orchestrateRequest(
            $validated['message'],
            isset($validated['project_id']) ? (int) $validated['project_id'] : null,
            isset($validated['conversation_id']) ? (int) $validated['conversation_id'] : null,
        );

        return response()->json([
            'status' => 'success',
            'response' => $result['response'],
            'meta' => [
                'created_task' => $result['created_task'],
                'task_type' => $result['task_type'],
                'project' => $result['project']?->name,
                'project_id' => $result['project']?->id,
                'conversation' => $result['conversation']?->title,
                'conversation_id' => $result['conversation']?->id,
                'task' => $result['task']?->name,
                'recommended_agent' => $result['artifact']['recommended_agent'] ?? $result['artifact']['draft']['recommended_agent'] ?? $result['task']?->recommendedAgent?->name,
                'pending_task_draft' => $result['artifact']['draft'] ?? null,
                'process_logs' => $this->processLogService->latestLogs(10)->map(fn ($log) => [
                    'kind' => $log->kind,
                    'status' => $log->status,
                    'message' => $log->message,
                    'created_at' => $log->created_at?->toISOString(),
                ])->all(),
                'external_sessions' => $this->processLogService->latestExternalSessions(6)->map(fn ($session) => [
                    'harness' => $session->harness,
                    'external_id' => $session->external_id,
                    'status' => $session->status,
                    'title' => $session->title,
                    'last_seen_at' => $session->last_seen_at?->toISOString(),
                ])->all(),
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function storeProject(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'goals' => 'nullable|string',
        ]);

        $this->projectService->createProject([
            ...$validated,
            'status' => 'active',
        ]);

        return back();
    }

    public function updateProject(Request $request, int $project): RedirectResponse
    {
        $projectModel = $this->projectService->getProjectById($project);

        abort_unless($projectModel !== null, Response::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'goals' => 'nullable|string',
            'current_intent' => 'nullable|string|max:500',
            'status' => 'required|string|in:active,paused,blocked,complete',
            'workspace_path' => 'nullable|string|max:500',
        ]);

        $projectModel->update($validated);

        return back();
    }

    public function destroyProject(int $project): RedirectResponse
    {
        $projectModel = $this->projectService->getProjectById($project);

        abort_unless($projectModel !== null, Response::HTTP_NOT_FOUND);

        $projectModel->delete();

        return redirect('/');
    }

    public function storeConversation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'purpose' => 'nullable|string|max:255',
        ]);

        $project = $this->projectService->getProjectById((int) $validated['project_id']);
        abort_unless($project !== null, Response::HTTP_NOT_FOUND);

        $conversation = $this->projectConversationService->createConversation(
            $project,
            $validated['title'],
            $validated['purpose'] ?? 'main',
        );

        return redirect()->to('/coordinator?project_id='.$project->id.'&conversation_id='.$conversation->id);
    }

    public function storeWorkflow(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $this->workflowService->createWorkflow([
            ...$validated,
            'steps' => [
                ['name' => 'Initialize', 'description' => 'Capture the task scope'],
                ['name' => 'Research', 'description' => 'Gather supporting context'],
                ['name' => 'Finalize', 'description' => 'Store the next action'],
            ],
        ]);

        return back();
    }

    public function storeTask(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'workflow_id' => 'required|exists:workflows,id',
            'recommended_agent_id' => 'nullable|exists:agents,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|integer|min:0|max:100',
        ]);

        $task = $this->taskService->createTaskWithWorkflow(
            (int) $validated['project_id'],
            (int) $validated['workflow_id'],
            $validated['name'],
            $validated['description'] ?? null,
            isset($validated['recommended_agent_id']) ? (int) $validated['recommended_agent_id'] : null,
        );

        if (isset($validated['priority'])) {
            $task->update(['priority' => (int) $validated['priority']]);
        }

        return back();
    }

    public function updateTask(Request $request, int $taskId): RedirectResponse
    {
        $task = $this->taskService->getTaskById($taskId);

        abort_unless($task !== null, Response::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|in:pending,in-progress,completed,failed,draft',
            'priority' => 'nullable|integer|min:0|max:100',
            'recommended_agent_id' => 'nullable|exists:agents,id',
        ]);

        $this->taskService->updateTask($taskId, $validated);

        return back();
    }

    public function destroyTask(int $taskId): RedirectResponse
    {
        $task = $this->taskService->getTaskById($taskId);

        abort_unless($task !== null, Response::HTTP_NOT_FOUND);

        $this->taskService->deleteTask($taskId);

        return back();
    }

    public function storeAgent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tools_text' => 'nullable|string',
        ]);

        $this->agentService->createAgent([
            'name' => $validated['name'],
            'role' => $validated['role'],
            'description' => $validated['description'] ?? null,
            'tools' => $this->parseList($validated['tools_text'] ?? null),
        ]);

        return back();
    }

    public function updateAgent(Request $request, int $agentId): RedirectResponse
    {
        $agent = $this->agentService->getAgentById($agentId);

        abort_unless($agent !== null, Response::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tools_text' => 'nullable|string',
        ]);

        $this->agentService->updateAgent($agentId, [
            'name' => $validated['name'],
            'role' => $validated['role'],
            'description' => $validated['description'] ?? null,
            'tools' => $this->parseList($validated['tools_text'] ?? null),
        ]);

        return back();
    }

    public function destroyAgent(int $agentId): RedirectResponse
    {
        $agent = $this->agentService->getAgentById($agentId);

        abort_unless($agent !== null, Response::HTTP_NOT_FOUND);

        $this->agentService->deleteAgent($agentId);

        return back();
    }

    public function storeAgentRuntime(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'name' => 'required|string|max:255',
            'harness' => 'required|string|in:laravel_ai,opencode,claude_code,codex',
            'runtime_type' => 'required|string|max:255',
            'runtime_ref' => 'required|string|max:255',
            'description' => 'nullable|string',
            'provider_route_id' => 'nullable|exists:provider_routes,id',
            'provider_model_id' => 'nullable|exists:provider_models,id',
            'fallback_provider_route_id' => 'nullable|exists:provider_routes,id',
            'fallback_provider_model_id' => 'nullable|exists:provider_models,id',
            'tools_text' => 'nullable|string',
            'config_text' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'saves_documents' => 'nullable|boolean',
            'status' => 'required|string|in:active,disabled',
        ]);

        $this->assertModelBelongsToRoute(
            (int) ($validated['provider_model_id'] ?? 0),
            (int) ($validated['provider_route_id'] ?? 0),
            'provider_model_id',
        );

        $this->assertModelBelongsToRoute(
            (int) ($validated['fallback_provider_model_id'] ?? 0),
            (int) ($validated['fallback_provider_route_id'] ?? 0),
            'fallback_provider_model_id',
        );

        if ($this->truthy($request->input('is_default'))) {
            AgentRuntime::query()
                ->where('agent_id', $validated['agent_id'])
                ->where('harness', $validated['harness'])
                ->update(['is_default' => false]);
        }

        AgentRuntime::query()->create([
            ...$validated,
            'tools' => $this->parseList($validated['tools_text'] ?? null),
            'config' => $this->parseJsonObject($validated['config_text'] ?? null),
            'is_default' => $this->truthy($request->input('is_default')),
            'saves_documents' => $this->truthy($request->input('saves_documents')),
        ]);

        return back();
    }

    public function updateAgentRuntime(Request $request, int $agentRuntimeId): RedirectResponse
    {
        $runtime = AgentRuntime::query()->findOrFail($agentRuntimeId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'harness' => 'required|string|in:laravel_ai,opencode,claude_code,codex',
            'runtime_type' => 'required|string|max:255',
            'runtime_ref' => 'required|string|max:255',
            'description' => 'nullable|string',
            'provider_route_id' => 'nullable|exists:provider_routes,id',
            'provider_model_id' => 'nullable|exists:provider_models,id',
            'fallback_provider_route_id' => 'nullable|exists:provider_routes,id',
            'fallback_provider_model_id' => 'nullable|exists:provider_models,id',
            'tools_text' => 'nullable|string',
            'config_text' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'saves_documents' => 'nullable|boolean',
            'status' => 'required|string|in:active,disabled',
        ]);

        $this->assertModelBelongsToRoute(
            (int) ($validated['provider_model_id'] ?? 0),
            (int) ($validated['provider_route_id'] ?? 0),
            'provider_model_id',
        );

        $this->assertModelBelongsToRoute(
            (int) ($validated['fallback_provider_model_id'] ?? 0),
            (int) ($validated['fallback_provider_route_id'] ?? 0),
            'fallback_provider_model_id',
        );

        if ($this->truthy($request->input('is_default'))) {
            AgentRuntime::query()
                ->where('agent_id', $runtime->agent_id)
                ->where('harness', $validated['harness'])
                ->whereKeyNot($runtime->id)
                ->update(['is_default' => false]);
        }

        $runtime->update([
            ...$validated,
            'tools' => $this->parseList($validated['tools_text'] ?? null),
            'config' => $this->parseJsonObject($validated['config_text'] ?? null),
            'is_default' => $this->truthy($request->input('is_default')),
            'saves_documents' => $this->truthy($request->input('saves_documents')),
        ]);

        return back();
    }

    public function destroyAgentRuntime(int $agentRuntimeId): RedirectResponse
    {
        AgentRuntime::query()->findOrFail($agentRuntimeId)->delete();

        return back();
    }

    public function storeProvider(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:subscription,hybrid,api-only,API-key-based,CLI-tool-based',
            'api_protocol' => 'required|string|in:Anthropic-compatible,native,OpenAI-compatible',
            'status' => 'required|string|in:active,rate-limited,degraded,disabled',
            'capability_tags_text' => 'nullable|string',
            'priority_preferences_text' => 'nullable|string',
            'rate_limits_text' => 'nullable|string',
            'usage_snapshot_text' => 'nullable|string',
        ]);

        $this->providerService->createProvider([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'api_protocol' => $validated['api_protocol'],
            'status' => $validated['status'],
            'capability_tags' => $this->parseList($validated['capability_tags_text'] ?? null),
            'priority_preferences' => $this->parseJsonObject($validated['priority_preferences_text'] ?? null),
            'rate_limits' => $this->parseJsonObject($validated['rate_limits_text'] ?? null),
            'usage_snapshot' => $this->parseJsonObject($validated['usage_snapshot_text'] ?? null),
        ]);

        return back();
    }

    public function updateProvider(Request $request, int $providerId): RedirectResponse
    {
        $provider = $this->providerService->getProviderById($providerId);

        abort_unless($provider !== null, Response::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:subscription,hybrid,api-only,API-key-based,CLI-tool-based',
            'api_protocol' => 'required|string|in:Anthropic-compatible,native,OpenAI-compatible',
            'status' => 'required|string|in:active,rate-limited,degraded,disabled',
            'capability_tags_text' => 'nullable|string',
            'priority_preferences_text' => 'nullable|string',
            'rate_limits_text' => 'nullable|string',
            'usage_snapshot_text' => 'nullable|string',
        ]);

        $this->providerService->updateProvider($providerId, [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'api_protocol' => $validated['api_protocol'],
            'status' => $validated['status'],
            'capability_tags' => $this->parseList($validated['capability_tags_text'] ?? null),
            'priority_preferences' => $this->parseJsonObject($validated['priority_preferences_text'] ?? null),
            'rate_limits' => $this->parseJsonObject($validated['rate_limits_text'] ?? null),
            'usage_snapshot' => $this->parseJsonObject($validated['usage_snapshot_text'] ?? null),
        ]);

        return back();
    }

    public function destroyProvider(int $providerId): RedirectResponse
    {
        $provider = $this->providerService->getProviderById($providerId);

        abort_unless($provider !== null, Response::HTTP_NOT_FOUND);

        $this->providerService->deleteProvider($providerId);

        return back();
    }

    public function storeProviderRoute(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider_id' => 'required|exists:providers,id',
            'name' => 'required|string|max:255',
            'harness' => 'required|string|in:laravel_ai,opencode,claude_code,codex',
            'auth_mode' => 'required|string|in:api_key,chatgpt_oauth,provider_oauth',
            'credential_type' => 'nullable|string|max:255',
            'priority' => 'nullable|integer',
            'status' => 'required|string|in:active,degraded,rate-limited,disabled',
            'capability_tags_text' => 'nullable|string',
            'rate_limits_text' => 'nullable|string',
            'usage_snapshot_text' => 'nullable|string',
            'config_text' => 'nullable|string',
            'supports_tools' => 'nullable|boolean',
            'supports_structured_output' => 'nullable|boolean',
        ]);

        ProviderRoute::query()->create([
            ...$validated,
            'priority' => (int) ($validated['priority'] ?? 100),
            'capability_tags' => $this->parseList($validated['capability_tags_text'] ?? null),
            'rate_limits' => $this->parseJsonObject($validated['rate_limits_text'] ?? null),
            'usage_snapshot' => $this->parseJsonObject($validated['usage_snapshot_text'] ?? null),
            'config' => $this->parseJsonObject($validated['config_text'] ?? null),
            'supports_tools' => $this->truthy($request->input('supports_tools')),
            'supports_structured_output' => $this->truthy($request->input('supports_structured_output')),
        ]);

        return back();
    }

    public function updateProviderRoute(Request $request, int $providerRouteId): RedirectResponse
    {
        $route = ProviderRoute::query()->findOrFail($providerRouteId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'harness' => 'required|string|in:laravel_ai,opencode,claude_code,codex',
            'auth_mode' => 'required|string|in:api_key,chatgpt_oauth,provider_oauth',
            'credential_type' => 'nullable|string|max:255',
            'priority' => 'nullable|integer',
            'status' => 'required|string|in:active,degraded,rate-limited,disabled',
            'capability_tags_text' => 'nullable|string',
            'rate_limits_text' => 'nullable|string',
            'usage_snapshot_text' => 'nullable|string',
            'config_text' => 'nullable|string',
            'supports_tools' => 'nullable|boolean',
            'supports_structured_output' => 'nullable|boolean',
        ]);

        $route->update([
            ...$validated,
            'priority' => (int) ($validated['priority'] ?? 100),
            'capability_tags' => $this->parseList($validated['capability_tags_text'] ?? null),
            'rate_limits' => $this->parseJsonObject($validated['rate_limits_text'] ?? null),
            'usage_snapshot' => $this->parseJsonObject($validated['usage_snapshot_text'] ?? null),
            'config' => $this->parseJsonObject($validated['config_text'] ?? null),
            'supports_tools' => $this->truthy($request->input('supports_tools')),
            'supports_structured_output' => $this->truthy($request->input('supports_structured_output')),
        ]);

        return back();
    }

    public function destroyProviderRoute(int $providerRouteId): RedirectResponse
    {
        ProviderRoute::query()->findOrFail($providerRouteId)->delete();

        return back();
    }

    public function storeProviderModel(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider_route_id' => 'required|exists:provider_routes,id',
            'name' => 'required|string|max:255',
            'external_name' => 'nullable|string|max:255',
            'capabilities_text' => 'nullable|string',
            'context_window' => 'nullable|integer',
            'priority' => 'nullable|integer',
            'config_text' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'status' => 'required|string|in:active,disabled',
        ]);

        if ($this->truthy($request->input('is_default'))) {
            ProviderModel::query()->where('provider_route_id', $validated['provider_route_id'])->update(['is_default' => false]);
        }

        ProviderModel::query()->create([
            ...$validated,
            'capabilities' => $this->parseList($validated['capabilities_text'] ?? null),
            'context_window' => $validated['context_window'] ?? null,
            'priority' => (int) ($validated['priority'] ?? 100),
            'config' => $this->parseJsonObject($validated['config_text'] ?? null),
            'is_default' => $this->truthy($request->input('is_default')),
        ]);

        return back();
    }

    public function updateProviderModel(Request $request, int $providerModelId): RedirectResponse
    {
        $model = ProviderModel::query()->findOrFail($providerModelId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'external_name' => 'nullable|string|max:255',
            'capabilities_text' => 'nullable|string',
            'context_window' => 'nullable|integer',
            'priority' => 'nullable|integer',
            'config_text' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'status' => 'required|string|in:active,disabled',
        ]);

        if ($this->truthy($request->input('is_default'))) {
            ProviderModel::query()
                ->where('provider_route_id', $model->provider_route_id)
                ->whereKeyNot($model->id)
                ->update(['is_default' => false]);
        }

        $model->update([
            ...$validated,
            'capabilities' => $this->parseList($validated['capabilities_text'] ?? null),
            'context_window' => $validated['context_window'] ?? null,
            'priority' => (int) ($validated['priority'] ?? 100),
            'config' => $this->parseJsonObject($validated['config_text'] ?? null),
            'is_default' => $this->truthy($request->input('is_default')),
        ]);

        return back();
    }

    public function destroyProviderModel(int $providerModelId): RedirectResponse
    {
        ProviderModel::query()->findOrFail($providerModelId)->delete();

        return back();
    }

    public function runHeartbeat(): RedirectResponse
    {
        RunHeartbeatJob::dispatch('manual');

        return back();
    }

    /**
     * @return array<int, string>
     */
    protected function parseList(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(static fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseJsonObject(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function truthy(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'on', 'true'], true);
    }

    protected function assertModelBelongsToRoute(int $modelId, int $routeId, string $field): void
    {
        if ($modelId === 0 || $routeId === 0) {
            return;
        }

        $belongs = ProviderModel::query()
            ->where('id', $modelId)
            ->where('provider_route_id', $routeId)
            ->exists();

        abort_if(! $belongs, Response::HTTP_UNPROCESSABLE_ENTITY, "The selected {$field} does not belong to the chosen route.");
    }
}
