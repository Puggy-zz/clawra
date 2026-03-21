<?php

declare(strict_types=1);

namespace App\Agents;

use App\Services\AgentService;
use App\Services\AiService;
use App\Services\ProcessLogService;
use App\Services\ProjectConversationService;
use App\Services\ProjectService;
use App\Services\RuntimeExecutionService;
use App\Services\SimpleChatService;
use App\Services\SyntheticSearchService;
use App\Services\TaskService;
use App\Services\WorkflowService;
use Throwable;

class CoordinatorAgent
{
    protected string $primaryModel = 'synthetic';

    protected string $fallbackModel = 'gemini';

    protected ResearcherAgent $researcher;

    protected PlannerAgent $planner;

    protected DeveloperAgent $developer;

    protected ReviewerAgent $reviewer;

    protected TestWriterAgent $testWriter;

    protected ?SimpleChatService $simpleChatService;

    protected ?CoordinatorIntentAgent $intentAgent;

    protected ?AgentService $agentService;

    protected ?RuntimeExecutionService $runtimeExecutionService;

    protected ?ProcessLogService $processLogService;

    protected ?ProjectConversationService $projectConversationService;

    protected ?string $assignableAgentRoster = null;

    public function __construct(
        protected AiService $aiService,
        protected ?ProjectService $projectService = null,
        protected ?TaskService $taskService = null,
        protected ?WorkflowService $workflowService = null,
        ?SimpleChatService $simpleChatService = null,
        ?CoordinatorIntentAgent $intentAgent = null,
        ?ResearcherAgent $researcher = null,
        ?PlannerAgent $planner = null,
        ?DeveloperAgent $developer = null,
        ?ReviewerAgent $reviewer = null,
        ?TestWriterAgent $testWriter = null,
        ?AgentService $agentService = null,
        ?RuntimeExecutionService $runtimeExecutionService = null,
        ?ProcessLogService $processLogService = null,
        ?ProjectConversationService $projectConversationService = null,
    ) {
        $this->projectService = $projectService ?? app(ProjectService::class);
        $this->taskService = $taskService ?? app(TaskService::class);
        $this->workflowService = $workflowService ?? app(WorkflowService::class);
        $this->simpleChatService = $simpleChatService;
        $this->intentAgent = $intentAgent;
        $this->agentService = $agentService ?? app(AgentService::class);
        $this->runtimeExecutionService = $runtimeExecutionService ?? app(RuntimeExecutionService::class);
        $this->processLogService = $processLogService ?? app(ProcessLogService::class);
        $this->projectConversationService = $projectConversationService ?? app(ProjectConversationService::class);
        $this->researcher = $researcher ?? new ResearcherAgent($aiService, app(SyntheticSearchService::class), $this->agentService);
        $this->planner = $planner ?? new PlannerAgent($aiService, $this->agentService);
        $this->developer = $developer ?? new DeveloperAgent($aiService, $this->agentService);
        $this->reviewer = $reviewer ?? new ReviewerAgent($aiService, $this->agentService);
        $this->testWriter = $testWriter ?? new TestWriterAgent($aiService, $this->agentService);
    }

    public function processMessage(string $message, ?int $projectId = null, ?int $conversationId = null): string
    {
        return $this->orchestrateRequest($message, $projectId, $conversationId)['response'];
    }

    public function orchestrateRequest(string $message, ?int $projectId = null, ?int $conversationId = null): array
    {
        ['project' => $project, 'conversation' => $conversation] = $this->projectConversationService->resolveContext($projectId, $conversationId);
        $coordinator = $this->agentService?->getAgentByName('Clawra');

        $result = $this->draftTaskConversation($conversation, $message);
        $action = $result['action'];

        if ($action === 'create_tasks' && ! empty($result['tasks'])) {
            return $this->createTasksFromConversation($project, $conversation, $result['tasks'], $result['response'], $coordinator);
        }

        if ($action === 'update_task' && is_array($result['task']) && isset($result['task']['id'])) {
            return $this->updateTaskFromConversation($project, $conversation, $result['task'], $result['response'], $coordinator);
        }

        return [
            'response' => $result['response'],
            'project' => $project,
            'conversation' => $conversation,
            'task' => null,
            'artifact' => null,
            'task_type' => 'chat',
            'created_task' => false,
        ];
    }

    public function decomposeRequest(string $request): array
    {
        $prompt = "Decompose the following request into specific tasks. Return a JSON array with task objects that include type, description, and priority:\n\n{$request}";
        ['model' => $primaryModel, 'fallback_model' => $fallbackModel] = $this->resolveModels();
        $response = $this->aiService->promptWithFallback($prompt, $primaryModel, $fallbackModel ?? $this->fallbackModel);

        if ($response['success']) {
            $tasks = json_decode($response['text'], true) ?: [];

            return $this->enhanceTasks($tasks);
        }

        return $this->enhanceTasks([
            ['type' => 'research', 'description' => 'Gather context for the request', 'priority' => 'high'],
            ['type' => 'planning', 'description' => 'Create an execution plan for the request', 'priority' => 'high'],
            ['type' => 'general', 'description' => 'Track the request for coordinator follow-up', 'priority' => 'medium'],
        ]);
    }

    protected function enhanceTasks(array $tasks): array
    {
        foreach ($tasks as &$task) {
            $task['priority'] = $task['priority'] ?? 'medium';
            $task['estimated_time'] = $task['estimated_time'] ?? '1 hour';
        }

        return $tasks;
    }

    public function routeTask(array $task): array
    {
        $taskType = $task['type'] ?? 'general';
        $description = $task['description'] ?? '';

        if (in_array($taskType, ['development', 'testing', 'review'], true)) {
            return match ($taskType) {
                'development' => $this->executeRuntimeTask('Developer', $description, fn (): array => $this->developer->implementFeature($description)),
                'testing' => $this->executeRuntimeTask('Test Writer', $description, fn (): array => $this->testWriter->generateUnitTests($description)),
                'review' => $this->executeRuntimeTask('Reviewer', $description, fn (): array => $this->reviewer->reviewCode($description)),
            };
        }

        if (! $this->aiService->isAvailable()) {
            return [
                'agent' => $this->getAgentNameForType($taskType),
                'result' => [
                    'message' => "This is a simulated response for task: {$description}",
                    'details' => "In a full implementation, the {$this->getAgentNameForType($taskType)} agent would process this task.",
                ],
                'status' => 'completed',
            ];
        }

        return match ($taskType) {
            'planning' => ['agent' => 'Planner', 'result' => $this->planner->createPlan($description), 'status' => 'completed'],
            'research' => ['agent' => 'Researcher', 'result' => $this->researcher->conductResearch($description), 'status' => 'completed'],
            default => ['agent' => 'Coordinator', 'result' => ['message' => 'Task handled by coordinator: '.$description], 'status' => 'completed'],
        };
    }

    protected function getAgentNameForType(string $type): string
    {
        return match ($type) {
            'planning' => 'Planner',
            'research' => 'Researcher',
            'development' => 'Developer',
            'testing' => 'TestWriter',
            'review' => 'Reviewer',
            default => 'Coordinator',
        };
    }

    public function executeWorkflow(array $tasks): array
    {
        return collect($tasks)->map(fn (array $task): array => $this->routeTask($task))->all();
    }

    public function getAvailableAgents(): array
    {
        return [
            'Researcher' => $this->researcher,
            'Planner' => $this->planner,
            'Developer' => $this->developer,
            'Reviewer' => $this->reviewer,
            'TestWriter' => $this->testWriter,
        ];
    }

    protected function classifyRequestType(string $message): string
    {
        return 'general';
    }

    protected function decideIntent(string $message): array
    {
        $normalized = strtolower(trim($message));

        if ($normalized === '') {
            return [
                'create_task' => false,
                'task_type' => 'chat',
            ];
        }

        if ($this->intentAgent instanceof CoordinatorIntentAgent) {
            try {
                $rawResponse = trim((string) $this->intentAgent->prompt("Classify this user message: {$message}"));
                $decoded = json_decode($rawResponse, true);

                if (is_array($decoded) && array_key_exists('create_task', $decoded) && array_key_exists('task_type', $decoded)) {
                    $createTask = (bool) $decoded['create_task'];

                    return [
                        'create_task' => $createTask,
                        'task_type' => $createTask
                            ? (in_array($decoded['task_type'], ['planning', 'research', 'general'], true) ? $decoded['task_type'] : 'general')
                            : 'chat',
                    ];
                }
            } catch (Throwable) {
                return [
                    'create_task' => false,
                    'task_type' => 'chat',
                ];
            }
        }

        ['model' => $primaryModel, 'fallback_model' => $fallbackModel] = $this->resolveModels();
        $response = $this->aiService->promptWithFallback(
            "Classify whether the message should create a tracked task. Return JSON only with keys create_task (boolean) and task_type (planning, research, general, chat).\n\nMessage: {$message}",
            $primaryModel,
            $fallbackModel ?? $this->fallbackModel,
        );

        if ($response['success']) {
            $decoded = json_decode((string) $response['text'], true);

            if (is_array($decoded) && array_key_exists('create_task', $decoded) && array_key_exists('task_type', $decoded)) {
                $createTask = (bool) $decoded['create_task'];

                return [
                    'create_task' => $createTask,
                    'task_type' => $createTask
                        ? (in_array($decoded['task_type'], ['planning', 'research', 'general'], true) ? $decoded['task_type'] : 'general')
                        : 'chat',
                ];
            }
        }

        if (str_contains($normalized, 'plan')) {
            return ['create_task' => true, 'task_type' => 'planning'];
        }

        if (str_contains($normalized, 'research') || str_contains($normalized, 'investigate')) {
            return ['create_task' => true, 'task_type' => 'research'];
        }

        if (preg_match('/\b(build|create|track|implement|fix|update)\b/', $normalized) === 1) {
            return ['create_task' => true, 'task_type' => 'general'];
        }

        return [
            'create_task' => false,
            'task_type' => 'chat',
        ];
    }

    protected function respondConversationally(string $message): string
    {
        if ($this->simpleChatService instanceof SimpleChatService) {
            $response = $this->simpleChatService->respondTo($message);

            if (($response['text'] ?? '') !== '') {
                return $response['text'];
            }
        }

        return 'Hi - I am here and ready. Ask me to plan, research, or create a task when you want me to track work.';
    }

    protected function storeDraftConversationResult(
        \App\Models\Project $project,
        \App\Models\ProjectConversation $conversation,
        string $message,
        ?array $draft,
        ?array $existingDraft,
        string $response,
    ): array {
        if ($draft !== null) {
            $this->projectConversationService->storePendingTaskDraft($conversation, $draft);

            $kind = $existingDraft === null ? 'draft.created' : 'draft.updated';
            $messageText = $existingDraft === null ? 'Created a pending task draft.' : 'Updated the pending task draft.';

            $this->logProcess($kind, $draft['needs_clarification'] ? 'clarifying' : 'ready', $messageText, [
                'message' => $message,
                'draft' => $draft,
            ], $project, $conversation, null, $this->agentService?->getAgentByName('Clawra'));
        }

        return [
            'response' => $response,
            'project' => $project,
            'conversation' => $conversation,
            'task' => null,
            'artifact' => $draft !== null ? ['draft' => $draft] : null,
            'task_type' => $draft['workflow_type'] ?? 'chat',
            'created_task' => false,
        ];
    }

    protected function createTaskFromDraft(\App\Models\Project $project, \App\Models\ProjectConversation $conversation, array $draft): array
    {
        $taskType = $draft['workflow_type'] ?? 'general';
        $workflow = $this->workflowService->getDefaultWorkflowForType($taskType);
        $recommendedAgentName = $this->normalizeRecommendedAgent((string) ($draft['recommended_agent'] ?? 'Planner'));
        $recommendedAgentId = $draft['recommended_agent_id'] ?? $this->resolveRecommendedAgentId($recommendedAgentName);

        $task = $this->taskService->createTaskWithWorkflow(
            $project->id,
            $workflow->id,
            $draft['title'] ?? 'Coordinator task',
            $this->buildTaskDescriptionFromDraft($draft),
            $recommendedAgentId,
            $conversation->id,
        );

        $artifact = [
            'summary' => $draft['summary'] ?? '',
            'recommended_agent' => $recommendedAgentName,
            'recommended_agent_id' => $recommendedAgentId,
            'next_actions' => $draft['goals'] ?? [],
            'acceptance_criteria' => $draft['acceptance_criteria'] ?? [],
        ];

        $this->projectService->recordTask(
            $project,
            $task,
            $draft['summary'] ?? $draft['title'] ?? 'Coordinator-created task',
            [
                'task_type' => $taskType,
                'artifact' => $artifact,
            ],
        );
        $this->projectConversationService->clearPendingTaskDraft($conversation);
        $this->logProcess('task.created', 'completed', 'Created a task from the confirmed draft.', [
            'draft' => $draft,
            'recommended_agent' => $recommendedAgentName,
        ], $project, $conversation, $task, $this->agentService?->getAgentByName('Clawra'));

        return [
            'response' => sprintf(
                'Created `%s` in %s. I framed it as a %s task and the current recommendation is to start with %s.',
                $task->name,
                $project->name,
                $taskType,
                $recommendedAgentName
            ),
            'project' => $project,
            'conversation' => $conversation,
            'task' => $task,
            'artifact' => $artifact,
            'task_type' => $taskType,
            'created_task' => true,
        ];
    }

    /**
     * @return array{action: string, response: string, tasks: array<int, array<string, mixed>>, task: array<string, mixed>|null}
     */
    protected function draftTaskConversation(\App\Models\ProjectConversation $conversation, string $message): array
    {
        $response = $this->projectConversationService->prompt($conversation, $message);

        $structured = $response->toArray();

        if (is_array($structured)) {
            $action = $structured['action'] ?? 'chat';
            $action = in_array($action, ['chat', 'create_tasks', 'update_task'], true) ? $action : 'chat';

            $tasks = [];
            $task = null;

            if ($action === 'create_tasks' && is_array($structured['tasks'] ?? null) && count($structured['tasks']) > 0) {
                $tasks = collect($structured['tasks'])
                    ->filter(fn (mixed $t): bool => is_array($t))
                    ->map(function (array $t): ?array {
                        $name = $this->sanitizeStructuredString($t['name'] ?? null);

                        if ($name === null || $name === '') {
                            return null;
                        }

                        return [
                            'name' => $name,
                            'description' => $this->sanitizeStructuredString($t['description'] ?? null) ?? '',
                            'workflow_type' => $this->sanitizeStructuredString($t['workflow_type'] ?? null) ?? 'general',
                            'recommended_agent' => $this->sanitizeStructuredString($t['recommended_agent'] ?? null) ?? 'Planner',
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();
            }

            if ($action === 'update_task') {
                $taskName = $this->sanitizeStructuredString($structured['task_name'] ?? null);

                if ($taskName !== null && $taskName !== '') {
                    $task = [
                        'id' => isset($structured['task_id']) ? (int) $structured['task_id'] : null,
                        'name' => $taskName,
                        'description' => $this->sanitizeStructuredString($structured['task_description'] ?? null) ?? '',
                        'workflow_type' => $this->sanitizeStructuredString($structured['task_workflow_type'] ?? null) ?? 'general',
                        'recommended_agent' => $this->sanitizeStructuredString($structured['task_recommended_agent'] ?? null) ?? 'Planner',
                    ];
                }
            }

            return [
                'action' => $action,
                'response' => (string) ($structured['response'] ?? 'Okay.'),
                'tasks' => $tasks,
                'task' => $task,
            ];
        }

        return [
            'action' => 'chat',
            'response' => "I'm here. What would you like to work on?",
            'tasks' => [],
            'task' => null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $tasksData
     */
    protected function createTasksFromConversation(
        \App\Models\Project $project,
        \App\Models\ProjectConversation $conversation,
        array $tasksData,
        string $response,
        ?\App\Models\Agent $coordinator = null,
    ): array {
        $createdTasks = [];

        foreach ($tasksData as $taskData) {
            $taskType = in_array($taskData['workflow_type'] ?? 'general', ['planning', 'research', 'general'], true)
                ? ($taskData['workflow_type'] ?? 'general')
                : 'general';
            $workflow = $this->workflowService->getDefaultWorkflowForType($taskType);
            $recommendedAgentName = $this->normalizeRecommendedAgent((string) ($taskData['recommended_agent'] ?? 'Planner'));
            $recommendedAgentId = $this->resolveRecommendedAgentId($recommendedAgentName);

            $task = $this->taskService->createTaskWithWorkflow(
                $project->id,
                $workflow->id,
                $this->compactText((string) ($taskData['name'] ?? 'Task'), 120),
                $this->compactText((string) ($taskData['description'] ?? ''), 900),
                $recommendedAgentId,
                $conversation->id,
            );

            $this->projectService->recordTask($project, $task, $taskData['description'] ?? $taskData['name'] ?? '', [
                'task_type' => $taskType,
            ]);

            $this->logProcess('task.created', 'completed', "Created task: {$task->name}.", [
                'task' => $taskData,
            ], $project, $conversation, $task, $coordinator);

            $createdTasks[] = $task;
        }

        $firstTask = $createdTasks[0] ?? null;

        return [
            'response' => $response,
            'project' => $project,
            'conversation' => $conversation,
            'task' => $firstTask,
            'artifact' => ['tasks' => $tasksData],
            'task_type' => $firstTask ? ($tasksData[0]['workflow_type'] ?? 'general') : 'general',
            'created_task' => count($createdTasks) > 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $taskData
     */
    protected function updateTaskFromConversation(
        \App\Models\Project $project,
        \App\Models\ProjectConversation $conversation,
        array $taskData,
        string $response,
        ?\App\Models\Agent $coordinator = null,
    ): array {
        $taskId = (int) $taskData['id'];
        $task = $this->taskService->getTaskById($taskId);

        if (! $task instanceof \App\Models\Task || $task->project_id !== $project->id) {
            return [
                'response' => $response,
                'project' => $project,
                'conversation' => $conversation,
                'task' => null,
                'artifact' => null,
                'task_type' => 'chat',
                'created_task' => false,
            ];
        }

        $taskType = in_array($taskData['workflow_type'] ?? 'general', ['planning', 'research', 'general'], true)
            ? ($taskData['workflow_type'] ?? 'general')
            : 'general';
        $recommendedAgentName = $this->normalizeRecommendedAgent((string) ($taskData['recommended_agent'] ?? 'Planner'));
        $recommendedAgentId = $this->resolveRecommendedAgentId($recommendedAgentName);
        $workflow = $this->workflowService->getDefaultWorkflowForType($taskType);

        $this->taskService->updateTask($taskId, [
            'name' => $this->compactText((string) ($taskData['name'] ?? $task->name), 120),
            'description' => $this->compactText((string) ($taskData['description'] ?? $task->description ?? ''), 900),
            'workflow_id' => $workflow->id,
            'recommended_agent_id' => $recommendedAgentId,
        ]);

        $updatedTask = $this->taskService->getTaskById($taskId);

        $this->logProcess('task.updated', 'completed', "Updated task: {$task->name}.", [
            'task' => $taskData,
        ], $project, $conversation, $updatedTask, $coordinator);

        return [
            'response' => $response,
            'project' => $project,
            'conversation' => $conversation,
            'task' => $updatedTask,
            'artifact' => ['task' => $taskData],
            'task_type' => $taskType,
            'created_task' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @param  array<string, mixed>|null  $existingDraft
     * @return array<string, mixed>
     */
    protected function normalizeDraft(array $draft, string $taskType, ?array $existingDraft = null): array
    {
        $recommendedAgent = $this->normalizeRecommendedAgent((string) ($draft['recommended_agent'] ?? $existingDraft['recommended_agent'] ?? 'Planner'));

        return [
            'title' => $this->compactText((string) ($draft['title'] ?? $existingDraft['title'] ?? 'Coordinator task'), 120),
            'summary' => $this->compactText((string) ($draft['summary'] ?? $existingDraft['summary'] ?? ''), 220),
            'description' => $this->compactText((string) ($draft['description'] ?? $existingDraft['description'] ?? ''), 900),
            'workflow_type' => in_array($draft['workflow_type'] ?? $taskType, ['planning', 'research', 'general'], true)
                ? ($draft['workflow_type'] ?? $taskType)
                : $taskType,
            'recommended_agent' => $recommendedAgent,
            'recommended_agent_id' => $this->resolveRecommendedAgentId($recommendedAgent),
            'needs_clarification' => (bool) ($draft['needs_clarification'] ?? false),
            'clarifying_questions' => collect($draft['clarifying_questions'] ?? $existingDraft['clarifying_questions'] ?? [])->filter()->values()->all(),
            'goals' => collect($draft['goals'] ?? $existingDraft['goals'] ?? [])->filter()->values()->all(),
            'acceptance_criteria' => collect($draft['acceptance_criteria'] ?? $existingDraft['acceptance_criteria'] ?? [])->filter()->values()->all(),
        ];
    }

    protected function defaultDraftResponse(array $draft): string
    {
        if ($draft['needs_clarification']) {
            $questions = collect($draft['clarifying_questions'] ?? [])->take(3)->implode(' ');

            return trim('I want to frame this task properly before I create it. '.$questions);
        }

        return sprintf(
            'Here is the task draft I would create: `%s` - %s. Tell me to create it when you are ready, or tell me what to adjust.',
            $draft['title'],
            $draft['summary']
        );
    }

    protected function buildTaskDescriptionFromDraft(array $draft): string
    {
        $sections = array_filter([
            $draft['description'] ?? $draft['summary'] ?? null,
            ! empty($draft['goals']) ? 'Goals: '.implode('; ', $draft['goals']) : null,
            ! empty($draft['acceptance_criteria']) ? 'Acceptance criteria: '.implode('; ', $draft['acceptance_criteria']) : null,
            ! empty($draft['recommended_agent']) ? 'Recommended starting agent: '.$draft['recommended_agent'] : null,
        ]);

        return $this->compactText(implode("\n\n", $sections), 1200);
    }

    protected function buildAssignableAgentRoster(): string
    {
        if ($this->assignableAgentRoster !== null) {
            return $this->assignableAgentRoster;
        }

        $agents = $this->agentService instanceof AgentService
            ? $this->agentService->getAssignableAgents()
            : collect();

        if ($agents->isEmpty()) {
            return "- Planner: planning specialist\n- Researcher: research specialist\n- Developer: implementation specialist\n- Reviewer: review specialist\n- Test Writer: testing specialist";
        }

        $this->assignableAgentRoster = $agents
            ->map(function ($agent): string {
                $tools = collect($agent->tools ?? [])->take(4)->implode(', ');

                return sprintf(
                    '- %s: %s. %s%s',
                    $agent->name,
                    $agent->role,
                    $agent->description ?? 'No description provided.',
                    $tools !== '' ? ' Tools: '.$tools.'.' : ''
                );
            })
            ->implode("\n");

        return $this->assignableAgentRoster;
    }

    protected function normalizeRecommendedAgent(string $agentName): string
    {
        $fallback = 'Planner';

        if (! $this->agentService instanceof AgentService) {
            return $agentName === 'Clawra' || $agentName === '' ? $fallback : $agentName;
        }

        $match = $this->agentService->getAssignableAgents()->first(function ($agent) use ($agentName) {
            return strcasecmp($agent->name, $agentName) === 0;
        });

        return $match?->name ?? $fallback;
    }

    protected function resolveRecommendedAgentId(string $agentName): ?int
    {
        if (! $this->agentService instanceof AgentService) {
            return null;
        }

        return $this->agentService->getAssignableAgents()->first(function ($agent) use ($agentName) {
            return strcasecmp($agent->name, $agentName) === 0;
        })?->id;
    }

    protected function buildResponse(string $projectName, string $taskName, string $taskType, array $artifact, string $workflowName): string
    {
        $summary = match ($taskType) {
            'planning' => $artifact['summary'] ?? 'Planning artifact created.',
            'research' => $artifact['summary'] ?? 'Research artifact created.',
            default => 'Request decomposed and queued for follow-up.',
        };

        return sprintf(
            'Captured this as a %s task in %s using the %s workflow. Task: %s. %s',
            $taskType,
            $projectName,
            $workflowName,
            $taskName,
            $this->compactText((string) $summary, 320),
        );
    }

    protected function sanitizeStructuredString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $str = trim((string) $value, " \t\n\r\0\x0B\"'");

        if ($str === '' || strtolower($str) === 'null') {
            return null;
        }

        return $str;
    }

    protected function compactText(string $text, int $limit = 320): string
    {
        return str($text)->squish()->limit($limit)->value();
    }

    protected function executeRuntimeTask(string $agentName, string $description, callable $fallback): array
    {
        $agent = $this->agentService?->getAgentByName($agentName);

        $this->logProcess('task.routed', 'started', sprintf('Routing task to %s.', $agentName), [
            'description' => $description,
        ], null, null, null, $agent);

        if ($this->runtimeExecutionService instanceof RuntimeExecutionService) {
            $result = $this->runtimeExecutionService->executeAgent($agentName, $description);

            if ($result['success']) {
                $this->logProcess('task.routed', 'completed', sprintf('%s completed runtime execution.', $agentName), $result, null, null, null, $agent);

                return [
                    'agent' => $agentName,
                    'result' => [
                        'message' => $result['text'],
                        'harness' => $result['harness'] ?? null,
                        'runtime' => $result['runtime'] ?? null,
                        'external_session_id' => $result['external_session_id'] ?? null,
                        'external_session_ref' => $result['external_session_ref'] ?? null,
                    ],
                    'status' => $result['status'],
                ];
            }
        }

        $this->logProcess('task.routed', 'fallback', sprintf('%s fell back to local execution.', $agentName), [
            'description' => $description,
        ], null, null, null, $agent);

        return [
            'agent' => $agentName,
            'result' => $fallback(),
            'status' => 'completed',
        ];
    }

    /**
     * @return array{model: string, fallback_model: ?string}
     */
    protected function resolveModels(): array
    {
        if (! $this->agentService instanceof AgentService) {
            return [
                'model' => $this->primaryModel,
                'fallback_model' => $this->fallbackModel,
            ];
        }

        $config = $this->agentService->getLaravelAiConfigForAgent('Clawra', 'synthetic', 'gemini', $this->primaryModel, $this->fallbackModel);

        return [
            'model' => $config['model'] ?? $this->primaryModel,
            'fallback_model' => $config['fallback_model'] ?? $this->fallbackModel,
        ];
    }

    protected function logProcess(
        string $kind,
        string $status,
        string $message,
        array $context = [],
        ?\App\Models\Project $project = null,
        ?\App\Models\ProjectConversation $conversation = null,
        ?\App\Models\Task $task = null,
        ?\App\Models\Agent $agent = null,
        ?\App\Models\AgentRuntime $agentRuntime = null,
    ): void {
        if (! $this->processLogService instanceof ProcessLogService) {
            return;
        }

        $this->processLogService->log(
            $kind,
            $status,
            $message,
            $context,
            project: $project,
            conversation: $conversation,
            task: $task,
            agent: $agent,
            agentRuntime: $agentRuntime,
        );
    }
}
