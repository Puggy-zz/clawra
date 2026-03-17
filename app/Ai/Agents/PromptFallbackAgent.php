<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

class PromptFallbackAgent implements Agent
{
    use Promptable;

    public function __construct(
        protected string $instructionsText = 'You are a helpful software engineering assistant.'
    ) {}

    public function instructions(): string
    {
        return $this->instructionsText;
    }

    public function timeout(): int
    {
        return (int) config('services.clawra.agent_timeout', 12);
    }
}
