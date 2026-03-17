<?php

declare(strict_types=1);

namespace App\Agents;

use App\Services\AgentService;
use App\Services\AiService;
use App\Services\SyntheticSearchService;

class ResearcherAgent
{
    protected string $summaryModel = 'synthetic';

    protected string $fallbackModel = 'gemini';

    public function __construct(
        protected AiService $aiService,
        protected SyntheticSearchService $syntheticSearchService,
        protected ?AgentService $agentService = null,
    ) {}

    public function conductResearch(string $query): array
    {
        $results = $this->syntheticSearchService->search($query);
        $formattedResults = $this->syntheticSearchService->formatResults($results);
        $summary = $this->summarizeFindings($formattedResults);

        return [
            'query' => $query,
            'results' => $this->syntheticSearchService->compactResults($results),
            'summary' => $summary,
            'sources' => collect($results)->pluck('url')->filter()->take(5)->values()->all(),
            'timestamp' => now()->toISOString(),
        ];
    }

    public function summarizeFindings(string $content): string
    {
        $prompt = "Summarize the following research content in no more than three short bullets and one closing sentence. Focus on the most relevant findings only.\n\n{$content}";
        ['model' => $summaryModel, 'fallback_model' => $fallbackModel] = $this->resolveModels();
        $response = $this->aiService->promptWithFallback($prompt, $summaryModel, $fallbackModel ?? $this->fallbackModel);

        return $response['success']
            ? str($response['text'])->squish()->limit(600)->value()
            : 'Research captured. Open the task for source links and next steps.';
    }

    public function extractFacts(string $content): array
    {
        $prompt = "Extract key facts from the following content. Return as a JSON array:\n\n{$content}";
        ['model' => $summaryModel, 'fallback_model' => $fallbackModel] = $this->resolveModels();
        $response = $this->aiService->promptWithFallback($prompt, $summaryModel, $fallbackModel ?? $this->fallbackModel);

        return $response['success'] ? (json_decode($response['text'], true) ?: []) : [];
    }

    /**
     * @return array{model: string, fallback_model: ?string}
     */
    protected function resolveModels(): array
    {
        if (! $this->agentService instanceof AgentService) {
            return [
                'model' => $this->summaryModel,
                'fallback_model' => $this->fallbackModel,
            ];
        }

        $config = $this->agentService->getLaravelAiConfigForAgent('Researcher', 'synthetic', 'gemini', $this->summaryModel, $this->fallbackModel);

        return [
            'model' => $config['model'] ?? $this->summaryModel,
            'fallback_model' => $config['fallback_model'] ?? $this->fallbackModel,
        ];
    }
}
