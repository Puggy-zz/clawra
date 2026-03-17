<?php

declare(strict_types=1);

namespace App\Services;

use App\Ai\Agents\ClawraCoordinatorConversationAgent;
use App\Models\Project;
use App\Models\ProjectConversation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Responses\StructuredAgentResponse;

class ProjectConversationService
{
    public function __construct(protected AgentService $agentService) {}

    public function getProjectConversationById(int $id): ?ProjectConversation
    {
        return ProjectConversation::query()->with('project')->find($id);
    }

    public function getConversationsForProject(Project $project): Collection
    {
        return ProjectConversation::query()
            ->where('project_id', $project->id)
            ->orderByDesc('is_default')
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function ensureDefaultConversation(Project $project): ProjectConversation
    {
        $conversation = ProjectConversation::query()->firstOrCreate(
            [
                'project_id' => $project->id,
                'is_default' => true,
            ],
            [
                'title' => 'Main conversation',
                'purpose' => 'main',
                'status' => 'active',
                'state_document' => $this->defaultStateDocument($project),
            ],
        );

        return $conversation->refresh();
    }

    public function createConversation(Project $project, string $title, string $purpose = 'main'): ProjectConversation
    {
        return ProjectConversation::query()->create([
            'project_id' => $project->id,
            'title' => $title,
            'purpose' => $purpose,
            'status' => 'active',
            'is_default' => false,
            'state_document' => $this->defaultStateDocument($project),
        ])->refresh();
    }

    /**
     * @return array{project: Project, conversation: ProjectConversation, conversations: Collection<int, ProjectConversation>}
     */
    public function resolveContext(?int $projectId = null, ?int $conversationId = null): array
    {
        $project = $projectId !== null
            ? app(ProjectService::class)->getProjectById($projectId)
            : app(ProjectService::class)->ensureInboxProject();

        if (! $project instanceof Project) {
            $project = app(ProjectService::class)->ensureInboxProject();
        }

        $conversation = $conversationId !== null
            ? ProjectConversation::query()->where('project_id', $project->id)->find($conversationId)
            : null;

        $conversation ??= $this->ensureDefaultConversation($project);

        return [
            'project' => $project,
            'conversation' => $conversation,
            'conversations' => $this->getConversationsForProject($project),
        ];
    }

    public function getPendingTaskDraft(ProjectConversation $conversation): ?array
    {
        $state = $conversation->state_document ?? [];
        $draft = $state['pending_task_draft'] ?? null;

        return is_array($draft) ? $draft : null;
    }

    public function storePendingTaskDraft(ProjectConversation $conversation, array $draft): void
    {
        $state = $conversation->state_document ?? $this->defaultStateDocument($conversation->project);
        $state['pending_task_draft'] = $draft;
        $state['updated_at'] = now()->toISOString();

        $conversation->update([
            'state_document' => $state,
        ]);
    }

    public function clearPendingTaskDraft(ProjectConversation $conversation): void
    {
        $state = $conversation->state_document ?? $this->defaultStateDocument($conversation->project);
        unset($state['pending_task_draft']);
        $state['updated_at'] = now()->toISOString();

        $conversation->update([
            'state_document' => $state,
        ]);
    }

    public function prompt(ProjectConversation $conversation, string $message): StructuredAgentResponse
    {
        $agent = new ClawraCoordinatorConversationAgent(
            $conversation,
            $conversation->project,
            $this->agentService->getAssignableAgents(),
        );

        $config = $this->agentService->getLaravelAiConfigForAgent('Clawra', 'synthetic', 'gemini', 'deepseek-v3', 'gemini-2.5-pro');

        $response = $conversation->laravel_ai_conversation_id
            ? $agent->continue($conversation->laravel_ai_conversation_id, as: $conversation)
                ->prompt($message, provider: $config['provider'], model: $config['model'])
            : $agent->forUser($conversation)
                ->prompt($message, provider: $config['provider'], model: $config['model']);

        $conversation->update([
            'laravel_ai_conversation_id' => $response->conversationId ?? $conversation->laravel_ai_conversation_id,
            'last_message_at' => now(),
        ]);

        $this->replaceLatestConversationMessageContent(
            $conversation,
            role: 'assistant',
            content: (string) ($response['response'] ?? $response->text)
        );

        return $response;
    }

    /**
     * @return array<int, array{role: string, content: string, created_at: ?string}>
     */
    public function getMessages(ProjectConversation $conversation, int $limit = 100): array
    {
        if (! is_string($conversation->laravel_ai_conversation_id) || $conversation->laravel_ai_conversation_id === '') {
            return [];
        }

        return DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversation->laravel_ai_conversation_id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(function ($message): ?array {
                $content = $this->normalizeConversationMessageContent((string) $message->role, (string) $message->content);

                if ($content !== (string) $message->content && trim($content) !== '') {
                    DB::table('agent_conversation_messages')
                        ->where('id', $message->id)
                        ->update([
                            'content' => $content,
                            'updated_at' => now(),
                        ]);
                }

                if (trim($content) === '') {
                    return null;
                }

                return [
                    'role' => (string) $message->role,
                    'content' => trim($content),
                    'created_at' => $message->created_at,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function replaceLatestConversationMessageContent(ProjectConversation $conversation, string $role, string $content): void
    {
        if (! is_string($conversation->laravel_ai_conversation_id) || $conversation->laravel_ai_conversation_id === '') {
            return;
        }

        $messageId = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversation->laravel_ai_conversation_id)
            ->where('role', $role)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('id');

        if ($messageId === null) {
            return;
        }

        DB::table('agent_conversation_messages')
            ->where('id', $messageId)
            ->update([
                'content' => $content,
                'updated_at' => now(),
            ]);
    }

    protected function normalizeConversationMessageContent(string $role, string $content): string
    {
        if ($role === 'user' && str_starts_with($content, 'Latest user message: ')) {
            $normalized = preg_replace('/^Latest user message:\s*/', '', $content);
            $normalized = preg_replace('/\s*Current draft:\s*\{.*$/s', '', (string) $normalized);

            return trim((string) $normalized);
        }

        if ($role === 'assistant') {
            $trimmed = trim($content);

            if ($trimmed === '') {
                return '';
            }

            if ((str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) || (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']'))) {
                $decoded = json_decode($trimmed, true);

                if (is_array($decoded) && isset($decoded['response']) && is_string($decoded['response'])) {
                    return $decoded['response'];
                }
            }
        }

        return $content;
    }

    protected function defaultStateDocument(Project $project): array
    {
        return [
            'summary' => $project->description,
            'pending_task_draft' => null,
            'updated_at' => now()->toISOString(),
        ];
    }
}
