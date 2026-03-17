<?php

declare(strict_types=1);

namespace App\Agents;

use App\Services\AgentService;
use App\Services\AiService;

class TestWriterAgent
{
    /**
     * Primary model for the test writer.
     */
    protected string $primaryModel = 'synthetic';

    /**
     * Fallback model for the test writer.
     */
    protected string $fallbackModel = 'gemini';

    public function __construct(protected AiService $aiService, protected ?AgentService $agentService = null) {}

    /**
     * Generate unit tests for code.
     */
    public function generateUnitTests(string $code, string $description = ''): array
    {
        ['model' => $primaryModel, 'fallback_model' => $fallbackModel] = $this->resolveModels();
        $prompt = "Generate comprehensive PHPUnit unit tests for the following PHP/Laravel code:\n\n";
        if ($description) {
            $prompt .= "Description: {$description}\n\n";
        }
        $prompt .= "Code:\n{$code}\n\n";
        $prompt .= 'Return only the test code with proper assertions and coverage.';

        $response = $this->aiService->promptWithFallback($prompt, $primaryModel, $fallbackModel ?? $this->fallbackModel);

        if ($response['success']) {
            return $this->parseTests($response['text'], 'unit');
        }

        return [
            'tests' => '// Unit test generation failed',
            'framework' => 'phpunit',
            'coverage' => 'unknown',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Generate feature tests for functionality.
     */
    public function generateFeatureTests(string $functionality, string $requirements = ''): array
    {
        ['model' => $primaryModel] = $this->resolveModels();
        $prompt = "Generate comprehensive PestPHP feature tests for the following functionality:\n\n";
        $prompt .= "Functionality: {$functionality}\n\n";
        if ($requirements) {
            $prompt .= "Requirements: {$requirements}\n\n";
        }
        $prompt .= 'Return only the test code with proper assertions and coverage.';

        $response = $this->aiService->prompt($prompt, $primaryModel);

        if ($response['success']) {
            return $this->parseTests($response['text'], 'feature');
        }

        return $this->parseTests('// Placeholder feature tests', 'feature');
    }

    /**
     * Parse the tests response into a structured format.
     */
    protected function parseTests(string $testsText, string $type): array
    {
        return [
            'tests' => $testsText,
            'framework' => 'pest', // Using PestPHP as default
            'type' => $type,
            'coverage' => 'estimated',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Review and improve existing tests.
     */
    public function reviewTests(string $tests): array
    {
        ['model' => $primaryModel] = $this->resolveModels();
        $prompt = "Review and improve the following PHP/PestPHP tests. Add missing assertions, improve coverage, and follow best practices:\n\n{$tests}\n\n";
        $prompt .= 'Return the improved test code and a list of improvements made.';

        $response = $this->aiService->prompt($prompt, $primaryModel);

        if ($response['success']) {
            return $this->parseTestReview($response['text']);
        }

        return [
            'improved_tests' => $tests,
            'suggestions' => ['No improvements suggested due to error'],
            'coverage_improvement' => 'unknown',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Parse the test review response into a structured format.
     */
    protected function parseTestReview(string $reviewText): array
    {
        return [
            'improved_tests' => $reviewText,
            'suggestions' => ['Tests reviewed and improved'],
            'coverage_improvement' => 'improved',
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

        $config = $this->agentService->getLaravelAiConfigForAgent('Test Writer', 'synthetic', 'gemini', $this->primaryModel, $this->fallbackModel);

        return [
            'model' => $config['model'] ?? $this->primaryModel,
            'fallback_model' => $config['fallback_model'] ?? $this->fallbackModel,
        ];
    }
}
