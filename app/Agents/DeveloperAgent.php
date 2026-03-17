<?php

declare(strict_types=1);

namespace App\Agents;

use App\Services\AgentService;
use App\Services\AiService;

class DeveloperAgent
{
    /**
     * Primary model for the developer.
     */
    protected string $primaryModel = 'synthetic';

    /**
     * Fallback model for the developer.
     */
    protected string $fallbackModel = 'gemini';

    public function __construct(protected AiService $aiService, protected ?AgentService $agentService = null) {}

    /**
     * Implement a feature based on specifications.
     */
    public function implementFeature(string $specifications): array
    {
        $prompt = "Implement the following feature in PHP/Laravel. Return the code and a brief explanation:\n\n{$specifications}";

        ['model' => $primaryModel, 'fallback_model' => $fallbackModel] = $this->resolveModels();

        $response = $this->aiService->promptWithFallback($prompt, $primaryModel, $fallbackModel ?? $this->fallbackModel);

        if ($response['success']) {
            return $this->parseImplementation($response['text']);
        }

        return [
            'error' => 'Failed to implement feature',
            'message' => $response['error'] ?? 'Unknown error',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Parse the implementation response into a structured format.
     */
    protected function parseImplementation(string $implementationText): array
    {
        // This is a simplified parser - in a real implementation,
        // you would want more sophisticated parsing
        return [
            'code' => $implementationText,
            'language' => 'php',
            'explanation' => 'Implementation details',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Review and refactor existing code.
     */
    public function refactorCode(string $code): array
    {
        $prompt = "Review and refactor the following PHP/Laravel code. Return the improved code and a list of improvements made:\n\n{$code}";

        ['model' => $primaryModel] = $this->resolveModels();

        $response = $this->aiService->prompt($prompt, $primaryModel);

        if ($response['success']) {
            return $this->parseRefactoring($response['text']);
        }

        return [
            'refactored_code' => $code,
            'improvements' => ['No improvements suggested due to error'],
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Parse the refactoring response into a structured format.
     */
    protected function parseRefactoring(string $refactoringText): array
    {
        // This is a simplified parser - in a real implementation,
        // you would want more sophisticated parsing
        return [
            'refactored_code' => $refactoringText,
            'improvements' => ['Code refactored for better readability'],
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Generate unit tests for code.
     */
    public function generateTests(string $code, string $description): array
    {
        $prompt = "Generate PHPUnit tests for the following PHP/Laravel code:\n\nCode:\n{$code}\n\nDescription:\n{$description}\n\nReturn only the test code.";

        ['model' => $primaryModel] = $this->resolveModels();

        $response = $this->aiService->prompt($prompt, $primaryModel);

        if ($response['success']) {
            return [
                'tests' => $response['text'],
                'framework' => 'phpunit',
                'timestamp' => now()->toISOString(),
            ];
        }

        return [
            'tests' => '// Placeholder tests for: '.substr($description, 0, 50).'...',
            'framework' => 'phpunit',
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

        $config = $this->agentService->getLaravelAiConfigForAgent('Developer', 'synthetic', 'gemini', $this->primaryModel, $this->fallbackModel);

        return [
            'model' => $config['model'] ?? $this->primaryModel,
            'fallback_model' => $config['fallback_model'] ?? $this->fallbackModel,
        ];
    }
}
