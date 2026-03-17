<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class SimpleChatService
{
    public function __construct(
        protected SyntheticSearchService $syntheticSearchService,
        protected AiService $aiService,
        protected ?AgentService $agentService = null,
    ) {}

    /**
     * @return array{text: string, used_search: bool}
     */
    public function respondTo(string $message): array
    {
        try {
            ['model' => $primaryModel, 'fallback_model' => $fallbackModel] = $this->resolveModels();
            $response = $this->aiService->promptWithFallback(
                "Respond to the user like Clawra's coordinator assistant. Be concise, practical, and conversational. If the user is greeting you, greet them back briefly.\n\nUser message: {$message}",
                $primaryModel,
                $fallbackModel ?? 'gemini',
            );
            $responseText = trim((string) ($response['text'] ?? ''));

            if ($responseText !== '') {
                return [
                    'text' => $responseText,
                    'used_search' => false,
                ];
            }
        } catch (Throwable $exception) {
            Log::warning('Simple chat agent returned an error before fallback.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return $this->respondWithSearchFallback($message);
    }

    /**
     * @return array{text: string, used_search: bool}
     */
    protected function respondWithSearchFallback(string $message): array
    {
        try {
            $results = $this->syntheticSearchService->search($message);
            $formattedResults = $this->syntheticSearchService->formatResults($results);
            ['model' => $primaryModel, 'fallback_model' => $fallbackModel] = $this->resolveModels();

            $response = $this->aiService->promptWithFallback(
                prompt: "Answer the user's question using the web search results below. Be concise, and include the most relevant source URLs in the answer. If the results are insufficient, say so clearly.\n\nUser question: {$message}\n\nSearch results:\n{$formattedResults}",
                primaryModel: $primaryModel,
                fallbackModel: $fallbackModel ?? 'gemini'
            );

            $responseText = trim((string) ($response['text'] ?? ''));

            if ($responseText !== '') {
                return [
                    'text' => $responseText,
                    'used_search' => true,
                ];
            }

            return [
                'text' => $formattedResults,
                'used_search' => true,
            ];
        } catch (Throwable $exception) {
            Log::error('Search-backed chat fallback failed.', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'text' => 'I had trouble reaching the configured chat/search providers just now. Try again in a moment.',
                'used_search' => false,
            ];
        }
    }

    /**
     * @return array{model: string, fallback_model: ?string}
     */
    protected function resolveModels(): array
    {
        if (! $this->agentService instanceof AgentService) {
            return [
                'model' => 'synthetic',
                'fallback_model' => 'gemini',
            ];
        }

        $config = $this->agentService->getLaravelAiConfigForAgent('Clawra', 'synthetic', 'gemini', 'deepseek-v3', 'gemini-2.5-pro');

        return [
            'model' => $config['model'] ?? 'deepseek-v3',
            'fallback_model' => $config['fallback_model'] ?? 'gemini-2.5-pro',
        ];
    }
}
