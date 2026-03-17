<?php

declare(strict_types=1);

namespace App\Agents;

use App\Services\AgentService;
use App\Services\AiService;

class PlannerAgent
{
    protected string $primaryModel = 'synthetic';

    protected string $fallbackModel = 'gemini';

    public function __construct(protected AiService $aiService, protected ?AgentService $agentService = null) {}

    public function createPlan(string $requirements): array
    {
        $prompt = "Create a concise implementation plan for the following request. Return sections for summary, goals, milestones, and next actions.\n\n{$requirements}";
        ['model' => $primaryModel, 'fallback_model' => $fallbackModel] = $this->resolveModels();
        $response = $this->aiService->promptWithFallback($prompt, $primaryModel, $fallbackModel ?? $this->fallbackModel);

        if ($response['success']) {
            return $this->parsePlan($response['text']);
        }

        return $this->fallbackPlan($requirements, $response['error'] ?? null);
    }

    public function breakdownFeature(string $feature): array
    {
        $plan = $this->createPlan($feature);

        return $plan['next_actions'];
    }

    protected function parsePlan(string $planText): array
    {
        $lines = collect(preg_split('/\r\n|\r|\n/', trim($planText)) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values();

        $actions = $lines
            ->filter(fn (string $line): bool => str_starts_with($line, '-') || str_starts_with($line, '*') || preg_match('/^\d+[\).]/', $line) === 1)
            ->map(fn (string $line): array => ['title' => ltrim($line, "-*0123456789.) \t"), 'status' => 'pending'])
            ->values()
            ->all();

        return [
            'summary' => $lines->first() ?? 'Plan created.',
            'goals' => $lines->take(3)->values()->all(),
            'milestones' => $lines->take(5)->values()->all(),
            'next_actions' => $actions !== [] ? $actions : [
                ['title' => 'Review requirements and refine scope', 'status' => 'pending'],
                ['title' => 'Create the first implementation task', 'status' => 'pending'],
            ],
            'raw_plan' => $planText,
            'parsed_at' => now()->toISOString(),
        ];
    }

    protected function fallbackPlan(string $requirements, ?string $error = null): array
    {
        return [
            'summary' => 'Create a Phase 0 execution plan for the requested work.',
            'goals' => [$requirements],
            'milestones' => [
                'Clarify scope and data model impact',
                'Implement services and orchestration flow',
                'Validate with tests and a heartbeat pass',
            ],
            'next_actions' => [
                ['title' => 'Capture the request as a tracked task', 'status' => 'pending'],
                ['title' => 'Break the request into implementation milestones', 'status' => 'pending'],
                ['title' => 'Run targeted validation after implementation', 'status' => 'pending'],
            ],
            'raw_plan' => $error ? 'Planner fallback used: '.$error : 'Planner fallback used.',
            'parsed_at' => now()->toISOString(),
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

        $config = $this->agentService->getLaravelAiConfigForAgent('Planner', 'synthetic', 'gemini', $this->primaryModel, $this->fallbackModel);

        return [
            'model' => $config['model'] ?? $this->primaryModel,
            'fallback_model' => $config['fallback_model'] ?? $this->fallbackModel,
        ];
    }
}
