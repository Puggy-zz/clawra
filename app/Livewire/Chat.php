<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Jobs\ProcessChatMessageJob;
use App\Models\Project;
use App\Models\ProjectConversation;
use App\Services\ProjectConversationService;
use App\Services\ProjectService;
use Illuminate\View\View;
use Livewire\Component;

class Chat extends Component
{
    public string $message = '';

    public ?int $activeProjectId = null;

    public ?int $activeConversationId = null;

    public ?string $errorMessage = null;

    public function mount(ProjectConversationService $projectConversationService): void
    {
        $context = $projectConversationService->resolveContext(null, null);
        $this->activeProjectId = $context['project']->id;
        $this->activeConversationId = $context['conversation']->id;
    }

    public function updatedActiveProjectId(ProjectConversationService $projectConversationService): void
    {
        $context = $projectConversationService->resolveContext($this->activeProjectId, null);
        $this->activeProjectId = $context['project']->id;
        $this->activeConversationId = $context['conversation']->id;
        $this->errorMessage = null;
    }

    public function newConversation(ProjectConversationService $projectConversationService): void
    {
        $project = $this->activeProjectId !== null
            ? Project::query()->find($this->activeProjectId)
            : null;

        if (! $project instanceof Project) {
            return;
        }

        $conversation = $projectConversationService->createConversation(
            $project,
            'Conversation '.now()->format('M j, g:i a'),
        );

        $this->activeConversationId = $conversation->id;
        $this->errorMessage = null;
    }

    public function sendMessage(ProjectConversationService $projectConversationService): void
    {
        $trimmed = trim($this->message);

        if ($trimmed === '') {
            return;
        }

        $this->errorMessage = null;
        $this->lastCreatedCount = 0;

        $this->validate(['message' => ['required', 'string', 'max:1000']]);

        $conversation = $this->activeConversationId !== null
            ? ProjectConversation::query()->find($this->activeConversationId)
            : null;

        if (! $conversation instanceof ProjectConversation) {
            $this->errorMessage = 'No active conversation. Please refresh.';

            return;
        }

        $projectConversationService->storePendingMessage($conversation, $trimmed);

        ProcessChatMessageJob::dispatch(
            (int) $this->activeProjectId,
            (int) $this->activeConversationId,
            $trimmed,
        );

        $this->message = '';
    }

    public function render(
        ProjectService $projectService,
        ProjectConversationService $projectConversationService,
    ): View {
        $projects = $projectService->getAllProjects();

        // Load by stored IDs; fall back to resolveContext only if missing (e.g. fresh DB)
        $activeProject = $this->activeProjectId !== null
            ? Project::query()->find($this->activeProjectId)
            : null;

        $activeConversation = $this->activeConversationId !== null
            ? ProjectConversation::query()->find($this->activeConversationId)
            : null;

        if (! $activeProject instanceof Project || ! $activeConversation instanceof ProjectConversation) {
            $context = $projectConversationService->resolveContext($this->activeProjectId, null);
            $activeProject = $context['project'];
            $activeConversation = $context['conversation'];
            $this->activeProjectId = $activeProject->id;
            $this->activeConversationId = $activeConversation->id;
        }

        $projectConversations = $projectConversationService->getConversationsForProject($activeProject);
        $messages = $projectConversationService->getMessages($activeConversation, 60);
        $isProcessing = $projectConversationService->isProcessing($activeConversation);

        return view('livewire.chat', compact(
            'projects',
            'activeProject',
            'activeConversation',
            'projectConversations',
            'messages',
            'isProcessing',
        ));
    }
}
