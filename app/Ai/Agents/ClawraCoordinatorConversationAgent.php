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
        protected Collection $conversationTasks,
    ) {}

    public function instructions(): Stringable|string
    {
        $projectSummary = $this->project->description ?: 'No project summary is available yet.';
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

        $taskContext = $this->conversationTasks->isEmpty()
            ? "No tasks have been created in this conversation yet.\n"
            : "Tasks created in this conversation (reference these IDs for update_task):\n"
                .$this->conversationTasks
                    ->map(fn ($task): string => sprintf(
                        '- [ID:%d] %s (%s, assigned to %s)',
                        $task->id,
                        $task->name,
                        $task->workflow->name ?? 'general',
                        $task->recommendedAgent->name ?? 'Planner'
                    ))
                    ->implode("\n")
                ."\n";

        return "You are Clawra, an AI project coordinator. Your job is to turn user requests into actionable tasks.\n"
            ."Project: {$this->project->name}\n"
            ."Project summary: {$projectSummary}\n"
            ."Available specialist agents:\n{$agentRoster}\n"
            .$taskContext
            ."CRITICAL RULES — read carefully:\n"
            ."1. When the user asks you to create, make, track, research, plan, build, implement, or do ANY work: use action=create_tasks IMMEDIATELY and populate the tasks array. One item per task. Do NOT chat about it.\n"
            ."2. NEVER say 'I'll create a task' or 'I will do X' with action=chat. If you are going to create tasks, use action=create_tasks RIGHT NOW.\n"
            ."3. Use action=update_task when the user wants to change one existing task — set task_id to the ID from the context above, and fill in the other task_ fields.\n"
            ."4. Use action=chat ONLY for genuine conversation: greetings, follow-up questions, or when no work is being requested at all.\n"
            ."5. Never recommend Clawra as an agent. Only use the specialist agents listed above.\n"
            ."6. Keep task names short and actionable (under 80 characters). The response field should confirm what you just did.\n"
            ."7. tasks is ALWAYS required. For create_tasks populate it with one object per task. For chat and update_task return tasks as an empty array [].\n"
            .'8. Every task object MUST have name, description, workflow_type, and recommended_agent filled in — never null or omitted.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->enum(['chat', 'create_tasks', 'update_task'])->required(),
            'response' => $schema->string()->required(),
            'tasks' => $schema->array(
                $schema->object([
                    'name' => $schema->string()->required(),
                    'description' => $schema->string()->required(),
                    'workflow_type' => $schema->string()->enum(['planning', 'research', 'general'])->required(),
                    'recommended_agent' => $schema->string()->enum(['Planner', 'Researcher', 'Developer', 'Reviewer', 'Test Writer'])->required(),
                ])
            )->required(),
            'task_id' => $schema->integer()->nullable(),
            'task_name' => $schema->string()->nullable(),
            'task_description' => $schema->string()->nullable(),
            'task_workflow_type' => $schema->string()->enum(['planning', 'research', 'general'])->nullable(),
            'task_recommended_agent' => $schema->string()->enum(['Planner', 'Researcher', 'Developer', 'Reviewer', 'Test Writer'])->nullable(),
        ];
    }

    public function timeout(): int
    {
        return (int) config('services.clawra.agent_timeout', 12);
    }
}
