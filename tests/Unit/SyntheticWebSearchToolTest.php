<?php

declare(strict_types=1);

use App\Ai\Tools\SyntheticWebSearch;
use App\Services\SyntheticSearchService;
use Laravel\Ai\Tools\Request;

it('formats synthetic search tool output from the search service', function () {
    $service = Mockery::mock(SyntheticSearchService::class);
    $service->shouldReceive('search')
        ->once()
        ->with('latest laravel release', 3)
        ->andReturn([
            [
                'title' => 'Laravel News',
                'url' => 'https://laravel-news.com',
                'text' => 'Laravel release details',
                'published' => null,
            ],
        ]);
    $service->shouldReceive('formatResults')
        ->once()
        ->andReturn('1. Laravel News');

    $tool = new SyntheticWebSearch($service);
    $result = $tool->handle(new Request([
        'query' => 'latest laravel release',
        'limit' => 3,
    ]));

    expect($result)->toBe('1. Laravel News');
});
