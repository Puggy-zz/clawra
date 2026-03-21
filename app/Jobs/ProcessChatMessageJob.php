<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Agents\CoordinatorAgent;
use App\Models\ProjectConversation;
use App\Services\ProjectConversationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessChatMessageJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public readonly int $projectId,
        public readonly int $conversationId,
        public readonly string $message,
    ) {}

    public function handle(CoordinatorAgent $coordinatorAgent, ProjectConversationService $projectConversationService): void
    {
        try {
            $coordinatorAgent->orchestrateRequest($this->message, $this->projectId, $this->conversationId);
        } finally {
            $conversation = ProjectConversation::query()->find($this->conversationId);

            if ($conversation instanceof ProjectConversation) {
                $projectConversationService->clearPendingMessage($conversation);
            }
        }
    }
}
