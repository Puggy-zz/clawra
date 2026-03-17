<?php

declare(strict_types=1);

namespace App\Agents;

use App\Ai\Tools\SyntheticWebSearch;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

#[Provider('synthetic')]
#[Model('hf:deepseek-ai/DeepSeek-V3')]
class SimpleChatAgent implements Agent, HasTools
{
    use Promptable;

    public function timeout(): int
    {
        return (int) config('services.clawra.agent_timeout', 12);
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return 'You are a helpful AI assistant. Be friendly, concise, and helpful. If the user asks for current events, recent releases, web facts, or anything that needs live information, use the web search tool and include relevant source URLs in the answer.';
    }

    public function tools(): iterable
    {
        return [
            app(SyntheticWebSearch::class),
        ];
    }
}
