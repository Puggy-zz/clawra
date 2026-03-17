<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class SyntheticSearchService
{
    protected int $snippetLength = 280;

    /**
     * Search the web using Synthetic's native search endpoint.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws RequestException
     */
    public function search(string $query, int $limit = 5): array
    {
        $response = Http::acceptJson()
            ->timeout((int) config('services.clawra.search_timeout', 8))
            ->withToken((string) config('ai.providers.synthetic.key'))
            ->post(
                config('ai.providers.synthetic.search_url', 'https://api.synthetic.new/v2/search'),
                ['query' => $query]
            )
            ->throw();

        $results = Arr::get($response->json(), 'results', []);

        return array_slice(array_map(function (array $result): array {
            return [
                'title' => $result['title'] ?? '',
                'url' => $result['url'] ?? '',
                'text' => $result['text'] ?? '',
                'published' => $result['published'] ?? null,
            ];
        }, $results), 0, max(1, min($limit, 10)));
    }

    /**
     * Format raw search results for agent consumption.
     *
     * @param  array<int, array<string, mixed>>  $results
     */
    public function formatResults(array $results): string
    {
        if ($results === []) {
            return 'No search results were found.';
        }

        return collect($results)
            ->map(function (array $result, int $index): string {
                $title = $result['title'] ?: 'Untitled result';
                $url = $result['url'] ?: 'No URL provided';
                $text = trim((string) ($result['text'] ?? ''));
                $snippet = $text !== '' ? str($text)->squish()->limit($this->snippetLength)->value() : 'No summary available.';

                return sprintf(
                    "%d. %s\nURL: %s\nSnippet: %s",
                    $index + 1,
                    $title,
                    $url,
                    $snippet
                );
            })
            ->implode("\n\n");
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     * @return array<int, array<string, mixed>>
     */
    public function compactResults(array $results): array
    {
        return collect($results)
            ->map(function (array $result): array {
                return [
                    'title' => $result['title'] ?? '',
                    'url' => $result['url'] ?? '',
                    'snippet' => str((string) ($result['text'] ?? ''))->squish()->limit($this->snippetLength)->value(),
                    'published' => $result['published'] ?? null,
                ];
            })
            ->values()
            ->all();
    }
}
