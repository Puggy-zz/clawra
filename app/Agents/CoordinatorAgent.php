<?php

declare(strict_types=1);

namespace App\Agents;

use Illuminate\Support\Facades\Http;

class CoordinatorAgent
{
    /**
     * Primary model for the coordinator.
     */
    protected string $primaryModel = 'synthetic.new';

    /**
     * Fallback model for the coordinator.
     */
    protected string $fallbackModel = 'gemini';

    /**
     * Process a user message and coordinate with appropriate agents.
     */
    public function processMessage(string $message): string
    {
        // Check if AI facade is available
        if (!class_exists('\Laravel\Ai\Ai') && !class_exists('\Laravel\AI\Facades\AI')) {
            return "AI services are not currently available. This is a placeholder response for: {$message}";
        }

        try {
            // Try to use the primary model first
            if (class_exists('\Laravel\AI\Facades\AI')) {
                $response = \Laravel\AI\Facades\AI::using($this->primaryModel)->prompt($message);
                return $response->text();
            } else {
                return "AI services are not properly configured. This is a placeholder response for: {$message}";
            }
        } catch (\Exception $e) {
            // If primary model fails, use fallback model
            try {
                if (class_exists('\Laravel\AI\Facades\AI')) {
                    $response = \Laravel\AI\Facades\AI::using($this->fallbackModel)->prompt($message);
                    return $response->text();
                } else {
                    return "AI services are not properly configured. This is a placeholder response for: {$message}";
                }
            } catch (\Exception $fallbackException) {
                // If both models fail, return an error message
                return "Sorry, I'm currently unable to process your request. Please try again later.";
            }
        }
    }

    /**
     * Decompose a complex request into tasks.
     */
    public function decomposeRequest(string $request): array
    {
        // Check if AI facade is available
        if (!class_exists('\Laravel\Ai\Ai') && !class_exists('\Laravel\AI\Facades\AI')) {
            return [['task' => 'Placeholder task for: ' . $request]];
        }

        $prompt = "Decompose the following request into specific tasks:\n\n{$request}\n\nReturn a JSON array of tasks.";
        
        try {
            if (class_exists('\Laravel\AI\Facades\AI')) {
                $response = \Laravel\AI\Facades\AI::using($this->primaryModel)->prompt($prompt);
                return json_decode($response->text(), true) ?: [];
            } else {
                return [['task' => 'Placeholder task for: ' . $request]];
            }
        } catch (\Exception $e) {
            return [['task' => 'Placeholder task for: ' . $request]];
        }
    }

    /**
     * Route a task to the appropriate agent.
     */
    public function routeTask(array $task): string
    {
        $taskType = $task['type'] ?? 'general';
        
        switch ($taskType) {
            case 'planning':
                return "Task routed to Planner agent";
            case 'research':
                return "Task routed to Researcher agent";
            case 'development':
                return "Task routed to Developer agent";
            case 'testing':
                return "Task routed to Test Writer agent";
            case 'review':
                return "Task routed to Reviewer agent";
            default:
                return "Task routed to general coordinator";
        }
    }
}
