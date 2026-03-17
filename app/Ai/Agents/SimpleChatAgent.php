<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider('synthetic')]
#[Model('hf:deepseek-ai/DeepSeek-V3')]
class SimpleChatAgent implements Agent
{
    use Promptable;

    public function timeout(): int
    {
        return (int) config('services.clawra.agent_timeout', 12);
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'You are a helpful AI assistant. Be friendly, concise, and helpful in your responses. Answer questions clearly and provide useful information.';
    }
}
