<?php

declare(strict_types=1);

namespace App\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

#[Provider('synthetic')]
#[Model('hf:deepseek-ai/DeepSeek-V3')]
class CoordinatorIntentAgent implements Agent
{
    use Promptable;

    public function timeout(): int
    {
        return (int) config('services.clawra.agent_timeout', 12);
    }

    public function instructions(): string
    {
        return <<<'TEXT'
You are Clawra's intent classifier.

Return only valid JSON with this exact shape:
{
  "create_task": true or false,
  "task_type": "chat" | "planning" | "research" | "general",
  "reason": "short explanation"
}

Choose create_task=true only when the user is asking Clawra to track work, plan work, research something actionable, create a task, fix/build/review/implement something, or otherwise start meaningful work.
Choose create_task=false for greetings, small talk, acknowledgements, or purely conversational questions.
TEXT;
    }
}
