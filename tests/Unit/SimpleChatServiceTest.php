<?php

declare(strict_types=1);

use App\Services\AiService;
use App\Services\SimpleChatService;
use App\Services\SyntheticSearchService;

uses(Tests\TestCase::class);

it('returns the direct agent response when it is not blank', function () {
    $search = Mockery::mock(SyntheticSearchService::class);
    $aiService = Mockery::mock(AiService::class);
    $aiService->shouldReceive('promptWithFallback')->once()->andReturn([
        'success' => true,
        'text' => 'Hello back',
    ]);

    $service = new SimpleChatService($search, $aiService);

    expect($service->respondTo('Hello'))->toBe([
        'text' => 'Hello back',
        'used_search' => false,
    ]);
});

it('uses search-backed fallback when the agent returns a blank response', function () {
    $search = Mockery::mock(SyntheticSearchService::class);
    $search->shouldReceive('search')->once()->with('latest laravel release')->andReturn([
        ['title' => 'Laravel Releases', 'url' => 'https://github.com/laravel/framework/releases', 'text' => 'Latest release info'],
    ]);
    $search->shouldReceive('formatResults')->once()->andReturn('1. Laravel Releases\nURL: https://github.com/laravel/framework/releases');

    $aiService = Mockery::mock(AiService::class);
    $aiService->shouldReceive('promptWithFallback')->once()->andReturn([
        'success' => true,
        'text' => '   ',
    ]);
    $aiService->shouldReceive('promptWithFallback')->once()->andReturn([
        'success' => true,
        'text' => 'Laravel v12.54.0 - https://github.com/laravel/framework/releases/tag/v12.54.0',
    ]);

    $service = new SimpleChatService($search, $aiService);

    $response = $service->respondTo('latest laravel release');

    expect($response)
        ->toMatchArray([
            'used_search' => true,
        ])
        ->and($response['text'])
        ->toContain('https://github.com/laravel/framework/releases/tag/v12.54.0');
});
