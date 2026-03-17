<?php

declare(strict_types=1);

use App\Services\SyntheticSearchService;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

it('searches synthetic and normalizes results', function () {
    config()->set('ai.providers.synthetic.key', 'test-key');
    config()->set('ai.providers.synthetic.search_url', 'https://api.synthetic.new/v2/search');

    Http::fake([
        'https://api.synthetic.new/v2/search' => Http::response([
            'results' => [
                [
                    'title' => 'Synthetic Docs',
                    'url' => 'https://synthetic.new/docs',
                    'text' => 'Synthetic documentation',
                    'published' => '2025-11-05T00:00:00.000Z',
                ],
                [
                    'title' => 'Laravel AI',
                    'url' => 'https://laravel.com/docs/ai',
                    'text' => 'Laravel AI SDK docs',
                    'published' => null,
                ],
            ],
        ]),
    ]);

    $results = app(SyntheticSearchService::class)->search('synthetic docs', 1);

    expect($results)->toHaveCount(1)
        ->and($results[0]['title'])->toBe('Synthetic Docs')
        ->and($results[0]['url'])->toBe('https://synthetic.new/docs');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.synthetic.new/v2/search'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request['query'] === 'synthetic docs';
    });
});

it('formats synthetic search results for agent output', function () {
    $formatted = app(SyntheticSearchService::class)->formatResults([
        [
            'title' => 'Synthetic Docs',
            'url' => 'https://synthetic.new/docs',
            'text' => 'Synthetic documentation',
            'published' => '2025-11-05T00:00:00.000Z',
        ],
    ]);

    expect($formatted)
        ->toContain('Synthetic Docs')
        ->toContain('https://synthetic.new/docs')
        ->toContain('Synthetic documentation');
});
