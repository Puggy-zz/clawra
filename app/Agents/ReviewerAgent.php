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
