<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Models\Project;
use App\Models\ProjectConversation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class ClawraCoordinatorConversationAgent implements Agent, Conversational, HasStructuredOutput
{
    use Promptable;
    use RemembersConversations;

    public function __construct(
        protected ProjectConversation $conversation,
        protected Project $project,
        protected Collection $assignableAgents,
    ) {}

    public function instructions(): Stringable|string
    {
        $projectSummary = $this->project->description ?: 'No project summary is available yet.';
        $pendingDraft = $this->conversation->state_document['pending_task_draft'] ?? null;
        $agentRoster = $this->assignableAgents
            ->map(function ($agent): string {
                $tools = collect($agent->tools ?? [])->take(4)->implode(', ');

                return sprintf(
                    '- %s: %s. %s%s',
                    $agent->name,
                    $agent->role,
                    $agent->description ?? 'No description available.',
                    $tools !== '' ? ' Tools: '.$tools.'.' : ''
                );
            })
            ->implode("\n");

        $draftContext = is_array($pendingDraft)
            ? "Current pending draft:\n".json_encode($pendingDraft, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
            : 'There is currently no pending task draft for this conversation.\n';

        return "You are Clawra, the coordinator agent for this project conversation.\n"
            ."Project: {$this->project->name}\n"
            ."Project summary: {$projectSummary}\n"
            ."Conversation title: {$this->conversation->title}\n"
            ."Conversation purpose: {$this->conversation->purpose}\n"
            .$draftContext
            ."Available specialist agents:\n{$agentRoster}\n"
            ."Never recommend or assign work to Clawra itself. Only recommend one of the specialist agents above.\n"
            ."Decide the next action from the user's natural language.\n"
            ."Use action=chat for normal conversation with no draft changes.\n"
            ."Use action=draft when you should create or revise the pending draft.\n"
            ."Use action=create_task only when the user is clearly approving creation of a ready draft.\n"
            ."Use action=cancel_draft only when the user is clearly asking to abandon the current draft.\n"
            ."If details are missing, ask clarifying questions and keep needs_clarification=true.\n"
            .'If the task is sufficiently clear but the user has not approved creation yet, keep action=draft and ask for confirmation or revision in natural language.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->enum(['chat', 'draft', 'create_task', 'cancel_draft'])->required(),
            'response' => $schema->string()->required(),
            'draft' => $schema->object([
                'title' => $schema->string()->required(),
                'summary' => $schema->string()->required(),
                'description' => $schema->string()->required(),
                'workflow_type' => $schema->string()->enum(['planning', 'research', 'general'])->required(),
                'recommended_agent' => $schema->string()->enum(['Planner', 'Researcher', 'Developer', 'Reviewer', 'Test Writer'])->required(),
                'needs_clarification' => $schema->boolean()->required(),
                'clarifying_questions' => $schema->array($schema->string()),
                'goals' => $schema->array($schema->string()),
                'acceptance_criteria' => $schema->array($schema->string()),
            ])->nullable(),
        ];
    }

    public function timeout(): int
    {
        return (int) config('services.clawra.agent_timeout', 12);
    }
}
