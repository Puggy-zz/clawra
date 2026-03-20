<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;

class ResearchService
{
    protected string $searchUrl = 'https://api.synthetic.new/v2/search';

    protected string $searchTimeout = '8s';

    protected int $maxContextTokens = 256_000;

    protected int $maxSnippetLength = 280;

    /**
     * Limit to 1 parallel search initially for testing
     * Set to 3 after verifying synthetic.new /search API concurrency limits
     */
    protected array $maxParallelSearches = ['tasks' => [0], 'total' => 1];

    /**
     * Perform deep research on a topic using compound multi-query searches.
     *
     * @param  array{query: string, context?: string, depth?: 'basic'|'advanced'|'comprehensive', target_domains?: array<string, string>}  $taskContext
     * @return array{success: bool, output: string, findings: string, sources: array, cost: array, token_usage: array}
     */
    public function conductDeepResearch(
        string $query,
        ?string $context = null,
        string $depth = 'comprehensive',
        ?array $targetDomains = null,
        ?int $projectId = null,
        ?int $taskId = null
    ): array {
        $outputFile = $this->generateResearchOutputFile($query);
        $tokenUsage = [
            'input_tokens' => [],
            'output_tokens' => [],
            'total_input_tokens' => 0,
            'total_output_tokens' => 0,
            'total_cost' => 0,
            'context_remaining' => $this->maxContextTokens,
        ];

        $results = collect();

        // Multi-stage research based on depth
        if ($depth === 'comprehensive') {
            $results = $this->performComprehensiveResearch($query, $context, $targetDomains, $tokenUsage);
        } elseif ($depth === 'advanced') {
            $results = $this->performAdvancedResearch($query, $context, $targetDomains, $tokenUsage);
        } else {
            $results = collect([$this->performSearch($query, $tokenUsage)]);
        }

        // Generate comprehensive summary
        $fullContent = $this->formatAllResearchResults($results);
        $summary = $this->summarizeResearch($fullContent, $tokenUsage);
        $document = $this->createMarkdownDocument($query, $context, $summary, $results, $tokenUsage);

        // Save to file
        File::put($outputFile, $document);

        // Save document to database if projectId or taskId provided
        $savedDocumentId = null;
        if ($projectId !== null || $taskId !== null) {
            $savedDocumentId = $this->saveDocumentToDatabase(
                $query,
                $document,
                $outputFile,
                $projectId,
                $taskId
            );
        }

        return [
            'success' => true,
            'output' => $document,
            'findings' => $fullContent,
            'summary' => $summary,
            'sources' => collect($results)->pluck('url')->filter()->take(10)->values()->all(),
            'cost' => $tokenUsage,
            'output_file' => $outputFile,
            'document_id' => $savedDocumentId,
        ];
    }

    /**
     * Save research document to database
     */
    protected function saveDocumentToDatabase(
        string $title,
        string $content,
        string $filePath,
        ?int $projectId,
        ?int $taskId
    ): int {
        return \App\Models\Document::query()->create([
            'title' => $title,
            'content' => $content,
            'file_path' => $filePath,
            'file_name' => basename($filePath),
            'file_type' => 'md',
            'project_id' => $projectId,
            'task_id' => $taskId,
            'access_level' => $projectId ? 'project' : 'global',
            'metadata' => [
                'generated_by' => 'ResearchService',
                'model' => 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4',
                'generated_at' => now()->toIso8601String(),
            ],
        ])->id;
    }

    /**
     * @param  array{input_tokens: int, output_tokens: int, cost: float}  $currentUsage
     */
    protected function countTokenUsage(ResponseInterface $response): array
    {
        // In production, you'd call the llm usage endpoint
        // For now, we estimate gatekeeping logic would call an API endpoint for token counts
        return ['input_tokens' => 0, 'output_tokens' => 0];
    }

    protected function performComprehensiveResearch(
        string $query,
        ?string $context,
        ?array $targetDomains,
        array &$tokenUsage
    ): \Illuminate\Support\Collection {
        $multiQuery = [
            $query,
            $this->clarifySearchQueries($query, $context, []),
            $this->generateContrastingPerspectives($query, $context, []),
        ];

        $allResults = collect();

        foreach (array_slice($multiQuery, 0, $this->maxParallelSearches['tasks'][0]) as $searchQuery) {
            $results = $this->performSearch($searchQuery, $tokenUsage);
            $allResults = $allResults->concat(collect($results));

            if ($allResults->count() >= 15) {
                break;
            }
        }

        return $allResults;
    }

    protected function performAdvancedResearch(
        string $query,
        ?string $context,
        ?array $targetDomains,
        array &$tokenUsage
    ): \Illuminate\Support\Collection {
        // Focus on specific angle for advanced research
        $angle = $context ?? 'before you start';

        $multiQuery = [
            $query,
            sprintf('%s: key technical details and implementation specifics', $angle),
            sprintf('%s: recent developments and best practices', $angle),
        ];

        return collect([$this->performSearch($query, $tokenUsage)]);
    }

    protected function performSearch(string $query, array &$tokenUsage): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout((int) $this->searchTimeout)
                ->withToken((string) config('ai.providers.synthetic.key'))
                ->post(
                    $this->searchUrl,
                    ['query' => $query]
                )
                ->throw();

            $json = $response->json();

            return collect($json['results'] ?? [])
                ->map(function (array $result): array {
                    return [
                        'title' => $result['title'] ?? '',
                        'url' => $result['url'] ?? '',
                        'text' => $result['text'] ?? '',
                        'published' => $result['published'] ?? null,
                    ];
                })
                ->filter(fn (array $r) => ! empty($r['title']) && ! empty($r['url']))
                ->values()
                ->all();
        } catch (\Exception $e) {
            logger()->error("Search failed for query '{$query}': {$e->getMessage()}");

            return [];
        }
    }

    protected function formatAllResearchResults(\Illuminate\Support\Collection $results): string
    {
        if ($results->isEmpty()) {
            return 'No relevant research was found.';
        }

        return $results->sortByDesc('published')
            ->map(function (array $result, int $index): string {
                $title = $result['title'] ?? $result['name'] ?? 'Untitled result';
                $url = $result['url'] ?? $result['link'] ?? 'No URL provided';
                $text = trim((string) ($result['text'] ?? $result['content'] ?? ''));
                $published = $result['published'] ?? $result['date'] ?? '';
                $snippet = $text !== '' ? str($text)
                    ->squish()
                    ->limit($this->maxSnippetLength)
                    ->value()
                    : 'No summary available.';

                return sprintf(
                    "## %s\n\n**URL:** %s\n\n**Published:** %s\n\n%s\n\n",
                    $title,
                    $url,
                    $published,
                    $snippet
                );
            })
            ->implode("\n---\n\n");
    }

    protected function clarifySearchQueries(string $query, ?string $context = null, array $tokenUsage = []): string
    {
        $clarificationPrompt = 'Suggest 3 additional search queries that would help understand this topic better. Keep them focused, narrow, and relevant. Return as a JSON array.

**Original Query:** '.$query;

        if ($context) {
            $clarificationPrompt .= "\n**Context:** ".$context;
        }

        return $this->callLLM($clarificationPrompt, 'clarify', $tokenUsage);
    }

    protected function generateContrastingPerspectives(string $query, ?string $context = null, array $tokenUsage = []): string
    {
        $prompt = sprintf(
            "Suggest 3 search queries that would we look at this topic from ENCOMPPLICATING PERSPECTIVES. Return as a JSON array: ['perspective A', 'perspective B', 'perspective C'].

**Original query:** ".$query
        );

        if ($context) {
            $prompt .= "\n**Context:** ".$context;
        }

        return $this->callLLM($prompt, 'contrasting', $tokenUsage);
    }

    protected function summarizeResearch(string $content, array &$tokenUsage): string
    {
        $prompt = "Write a comprehensive, well-organized research report on the topic with the following sections. Structure it with clear headings for each section:\n\n**1. Executive Summary**\n- 3-5 sentences capturing key insights\n\n**2. Detailed Findings**\n- 3-5 subsections\n- Each subsection lists key points with relevant sources cited (use the source URLs provided in the research)\n- 2-4 sentences per subsection\n\n**3. Key Sources**\n- List all 3-5 primary source URLs used, with brief descriptions\n\n**4. Open Questions**\n- 1-3 questions that couldn't be definitively answered\n\n\n---\n\n{content}";

        $response = $this->callLLM($prompt, 'summarize', $tokenUsage);

        return $response;
    }

    protected function callLLM(string $prompt, string $purpose, array &$tokenUsage): string
    {
        // Use the AI SDK with fallbacks - in production, properly configured
        try {
            // For Phase 0, temporarily using direct HTTP until LLM SDK is properly configured
            // This would use $this->aiService->promptWithFallback() in production
            $response = Http::acceptJson()
                ->header('Authorization', 'Bearer '.config('ai.providers.synthetic.key'))
                ->post('https://api.synthetic.new/anthropic/v1/messages', [
                    'model' => 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4',
                    'max_tokens' => 2000,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ])
                ->throw();

            $json = $response->json();
            $tokens = $json['usage']['input_tokens'] ?? 0 + $json['usage']['output_tokens'] ?? 0;

            $tokenUsage['total_input_tokens'] += $json['usage']['input_tokens'] ?? 0;
            $tokenUsage['total_output_tokens'] += $json['usage']['output_tokens'] ?? 0;
            $tokenUsage['total_cost'] += $json['usage']['cost']['total_cost'] ?? 0;
            $tokenUsage['context_remaining'] -= $tokens;

            return $json['content'][0]['text'] ?? '';
        } catch (\Exception $e) {
            logger()->error("LLM call failed: {$e->getMessage()}");

            // Continue with basic response
            return 'Unable to generate additional research queries due to LLM errors.';
        }
    }

    protected function generateResearchOutputFile(string $query): string
    {
        $date = now()->format('Y-m-d-His');
        $cleanQuery = Str::slug($query);
        $filename = sprintf('research/%s/%s.md', $date, $cleanQuery);

        if (! File::exists(dirname($filename))) {
            File::makeDirectory(dirname($filename), 0755, true);
        }

        return $filename;
    }

    protected function createMarkdownDocument(
        string $query,
        ?string $context,
        string $summary,
        \Illuminate\Support\Collection $results,
        array &$tokenUsage
    ): string {
        $sources = collect($results)->pluck('url')->filter()->take(10);
        $sourceList = $sources->implode("\n- ");

        $document = "Research Report - {$query}\n\n";
        $document .= '**Context:** '.($context ?? 'No specific context provided')."\n";
        $document .= '**Date:** '.now()->format('F jS, Y')."\n";
        $document .= '**Token Usage:** '.implode(' / ', [$tokenUsage['total_input_tokens'] ?? 0, $tokenUsage['total_output_tokens'] ?? 0, $tokenUsage['total_cost'] ?? 0])." tokens\n\n";
        $document .= "---\n\n";
        $document .= "# Executive Summary\n\n";
        $document .= $summary."\n\n";
        $document .= "---\n\n";
        $document .= "# Sources\n\n";
        $document .= $sourceList ?: 'No sources found.';

        return $document;
    }
}
