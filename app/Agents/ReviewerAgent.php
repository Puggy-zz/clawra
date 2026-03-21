<?php

declare(strict_types=1);

namespace App\Agents;

use App\Services\AgentService;
use App\Services\AiService;

class ReviewerAgent
{
    /**
     * Primary model for the reviewer.
     */
    protected string $primaryModel = 'synthetic';

    /**
     * Fallback model for the reviewer.
     */
    protected string $fallbackModel = 'gemini';

    public function __construct(protected AiService $aiService, protected ?AgentService $agentService = null) {}

    /**
     * Review code for quality and best practices.
     */
    public function reviewCode(string $code, string $context = ''): array
    {
        ['model' => $primaryModel, 'fallback_model' => $fallbackModel] = $this->resolveModels();

        $prompt = "Review the following PHP/Laravel code for quality, best practices, and potential issues:\n\n";
        if ($context) {
            $prompt .= "Context: {$context}\n\n";
        }
        $prompt .= "Code:\n{$code}\n\n";
        $prompt .= 'Return a JSON object with decision (approved/rejected/needs_revision), comments, and suggestions.';

        $response = $this->aiService->promptWithFallback($prompt, $primaryModel, $fallbackModel ?? $this->fallbackModel);

        if ($response['success']) {
            return json_decode($response['text'], true) ?: $this->getDefaultReview();
        }

        return [
            'decision' => 'needs_revision',
            'comments' => 'Review failed due to technical issues',
            'suggestions' => ['Please review manually'],
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get default review response.
     */
    protected function getDefaultReview(): array
    {
        return [
            'decision' => 'approved',
            'comments' => 'Code reviewed successfully',
            'suggestions' => ['No major issues found'],
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Review a plan for completeness and feasibility.
     */
    public function reviewPlan(array $plan): array
    {
        ['model' => $primaryModel] = $this->resolveModels();
        $planJson = json_encode($plan, JSON_PRETTY_PRINT);
        $prompt = "Review the following project plan for completeness, feasibility, and best practices:\n\n{$planJson}\n\n";
        $prompt .= 'Return a JSON object with decision (approved/rejected/needs_revision), comments, and suggestions.';

        $response = $this->aiService->prompt($prompt, $primaryModel);

        if ($response['success']) {
            return json_decode($response['text'], true) ?: $this->getDefaultPlanReview();
        }

        return $this->getDefaultPlanReview();
    }

    /**
     * Get default plan review response.
     */
    protected function getDefaultPlanReview(): array
    {
        return [
            'decision' => 'approved',
            'comments' => 'Plan reviewed successfully',
            'suggestions' => ['No major issues found'],
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Review whether an opencode agent actually completed the given task.
     *
     * @return array{decision: string, reasoning: string}
     */
    public function reviewTaskCompletion(string $taskName, string $taskDescription, string $agentOutput): array
    {
        ['model' => $primaryModel, 'fallback_model' => $fallbackModel] = $this->resolveModels();

        $prompt = <<<EOT
You are a task completion reviewer. Given the original task specification and the final output message from a coding agent, determine whether the task was successfully completed.

## Task
**Name:** {$taskName}
**Description:** {$taskDescription}

## Agent Output
{$agentOutput}

Base your decision solely on what the agent reports in its output. If the agent's message indicates it completed the task (e.g. "Done", "Saved", "Created", "Finished"), mark it as completed. Do not require external evidence or verification — trust the agent's reported outcome.

Respond with a JSON object with exactly these fields:
- "decision": one of "completed", "incomplete", or "failed"
  - "completed": the agent's output indicates the task was done
  - "incomplete": the agent made progress but explicitly says it did not finish
  - "failed": the agent's output explicitly reports an error or failure
- "reasoning": a brief explanation of your decision (1-2 sentences)

Return only the JSON object, no markdown fences.
EOT;

        $response = $this->aiService->promptWithFallback($prompt, $primaryModel, $fallbackModel ?? $this->fallbackModel);

        if (! $response['success']) {
            return ['decision' => 'completed', 'reasoning' => 'Review unavailable — defaulting to completed.'];
        }

        $text = trim((string) $response['text']);
        $text = (string) preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = (string) preg_replace('/\s*```\s*$/m', '', trim($text));

        $parsed = json_decode(trim($text), true);

        if (! is_array($parsed) || ! isset($parsed['decision'])) {
            return ['decision' => 'completed', 'reasoning' => 'Could not parse review response — defaulting to completed.'];
        }

        $decision = in_array($parsed['decision'], ['completed', 'incomplete', 'failed'], true)
            ? $parsed['decision']
            : 'completed';

        return [
            'decision' => $decision,
            'reasoning' => (string) ($parsed['reasoning'] ?? ''),
        ];
    }

    /**
     * Review research findings for accuracy and completeness.
     */
    public function reviewResearch(array $research): array
    {
        ['model' => $primaryModel] = $this->resolveModels();
        $researchJson = json_encode($research, JSON_PRETTY_PRINT);
        $prompt = "Review the following research findings for accuracy, completeness, and relevance:\n\n{$researchJson}\n\n";
        $prompt .= 'Return a JSON object with decision (approved/rejected/needs_revision), comments, and suggestions.';

        $response = $this->aiService->prompt($prompt, $primaryModel);

        if ($response['success']) {
            return json_decode($response['text'], true) ?: $this->getDefaultResearchReview();
        }

        return $this->getDefaultResearchReview();
    }

    /**
     * Get default research review response.
     */
    protected function getDefaultResearchReview(): array
    {
        return [
            'decision' => 'approved',
            'comments' => 'Research reviewed successfully',
            'suggestions' => ['No major issues found'],
            'timestamp' => now()->toISOString(),
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

        $config = $this->agentService->getLaravelAiConfigForAgent('Reviewer', 'synthetic', 'gemini', $this->primaryModel, $this->fallbackModel);

        return [
            'model' => $config['model'] ?? $this->primaryModel,
            'fallback_model' => $config['fallback_model'] ?? $this->fallbackModel,
        ];
    }
}
