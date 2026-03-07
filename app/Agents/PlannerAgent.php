<?php

declare(strict_types=1);

namespace App\Agents;

class PlannerAgent
{
    /**
     * Primary model for the planner.
     */
    protected string $primaryModel = 'kimi-k2-instruct';

    /**
     * Fallback model for the planner.
     */
    protected string $fallbackModel = 'deepseek-v3';

    /**
     * Create a project plan from requirements.
     */
    public function createPlan(string $requirements): array
    {
        // Check if AI facade is available
        if (!class_exists('\Laravel\Ai\Ai') && !class_exists('\Laravel\AI\Facades\AI')) {
            return [
                'raw_plan' => 'Placeholder plan for: ' . $requirements,
                'parsed_at' => now()->toISOString(),
            ];
        }

        $prompt = "Create a detailed project plan based on the following requirements:\n\n{$requirements}\n\nReturn a structured plan with phases, tasks, and estimated effort.";
        
        try {
            if (class_exists('\Laravel\AI\Facades\AI')) {
                $response = \Laravel\AI\Facades\AI::using($this->primaryModel)->prompt($prompt);
                return $this->parsePlan($response->text());
            } else {
                return $this->parsePlan('Placeholder plan for: ' . $requirements);
            }
        } catch (\Exception $e) {
            try {
                if (class_exists('\Laravel\AI\Facades\AI')) {
                    $response = \Laravel\AI\Facades\AI::using($this->fallbackModel)->prompt($prompt);
                    return $this->parsePlan($response->text());
                } else {
                    return $this->parsePlan('Placeholder plan for: ' . $requirements);
                }
            } catch (\Exception $fallbackException) {
                return [
                    'error' => 'Failed to create plan',
                    'message' => $fallbackException->getMessage()
                ];
            }
        }
    }

    /**
     * Parse the plan response into a structured format.
     */
    protected function parsePlan(string $planText): array
    {
        // This is a simplified parser - in a real implementation, 
        // you would want more sophisticated parsing
        return [
            'raw_plan' => $planText,
            'parsed_at' => now()->toISOString(),
        ];
    }

    /**
     * Break down a feature into implementation tasks.
     */
    public function breakdownFeature(string $feature): array
    {
        // Check if AI facade is available
        if (!class_exists('\Laravel\Ai\Ai') && !class_exists('\Laravel\AI\Facades\AI')) {
            return [['task' => 'Placeholder task for: ' . $feature]];
        }

        $prompt = "Break down the following feature into specific implementation tasks:\n\n{$feature}\n\nReturn a JSON array of tasks with descriptions and estimated effort.";
        
        try {
            if (class_exists('\Laravel\AI\Facades\AI')) {
                $response = \Laravel\AI\Facades\AI::using($this->primaryModel)->prompt($prompt);
                return json_decode($response->text(), true) ?: [];
            } else {
                return [['task' => 'Placeholder task for: ' . $feature]];
            }
        } catch (\Exception $e) {
            return [['task' => 'Placeholder task for: ' . $feature]];
        }
    }
}
