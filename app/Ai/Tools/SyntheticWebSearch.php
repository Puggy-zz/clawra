<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Services\SyntheticSearchService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SyntheticWebSearch implements Tool
{
    public function __construct(protected SyntheticSearchService $syntheticSearchService) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Search the web for current information using Synthetic search and return relevant result URLs and snippets.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $results = $this->syntheticSearchService->search(
            query: (string) $request['query'],
            limit: (int) ($request['limit'] ?? 5),
        );

        return $this->syntheticSearchService->formatResults($results);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->required(),
            'limit' => $schema->integer()->min(1)->max(10),
        ];
    }
}
