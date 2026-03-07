<?php

declare(strict_types=1);

namespace App\Agents;

class ResearcherAgent
{
    /**
     * Primary model for the researcher (synthetic.new search endpoint).
     */
    protected string $searchModel = 'synthetic.new/search';

    /**
     * Conduct research on a topic.
     */
    public function conductResearch(string $query): array
    {
        // Check if AI facade is available
        if (!class_exists('\Laravel\Ai\Ai') && !class_exists('\Laravel\AI\Facades\AI')) {
            return [
                'query' => $query,
                'results' => 'Placeholder research results for: ' . $query,
                'timestamp' => now()->toISOString(),
            ];
        }

        $prompt = "Research the following topic and provide a comprehensive summary:\n\n{$query}";
        
        try {
            // Use the search endpoint for research
            if (class_exists('\Laravel\AI\Facades\AI')) {
                $response = \Laravel\AI\Facades\AI::using($this->searchModel)->prompt($prompt);
                
                return [
                    'query' => $query,
                    'results' => $response->text(),
                    'timestamp' => now()->toISOString(),
                ];
            } else {
                return [
                    'query' => $query,
                    'results' => 'Placeholder research results for: ' . $query,
                    'timestamp' => now()->toISOString(),
                ];
            }
        } catch (\Exception $e) {
            return [
                'query' => $query,
                'error' => 'Research failed',
                'message' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ];
        }
    }

    /**
     * Summarize research findings.
     */
    public function summarizeFindings(string $content): string
    {
        // Check if AI facade is available
        if (!class_exists('\Laravel\Ai\Ai') && !class_exists('\Laravel\AI\Facades\AI')) {
            return 'Placeholder summary for: ' . substr($content, 0, 50) . '...';
        }

        $prompt = "Summarize the following research content in a concise, structured format:\n\n{$content}";
        
        try {
            if (class_exists('\Laravel\AI\Facades\AI')) {
                $response = \Laravel\AI\Facades\AI::using($this->searchModel)->prompt($prompt);
                return $response->text();
            } else {
                return 'Placeholder summary for: ' . substr($content, 0, 50) . '...';
            }
        } catch (\Exception $e) {
            return "Failed to summarize findings: " . $e->getMessage();
        }
    }

    /**
     * Extract facts from research content.
     */
    public function extractFacts(string $content): array
    {
        // Check if AI facade is available
        if (!class_exists('\Laravel\Ai\Ai') && !class_exists('\Laravel\AI\Facades\AI')) {
            return [['fact' => 'Placeholder fact from content']];
        }

        $prompt = "Extract key facts from the following content. Return as a JSON array:\n\n{$content}";
        
        try {
            if (class_exists('\Laravel\AI\Facades\AI')) {
                $response = \Laravel\AI\Facades\AI::using($this->searchModel)->prompt($prompt);
                return json_decode($response->text(), true) ?: [];
            } else {
                return [['fact' => 'Placeholder fact from content']];
            }
        } catch (\Exception $e) {
            return [];
        }
    }
}
