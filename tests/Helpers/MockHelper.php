<?php

declare(strict_types=1);

use App\Services\AiService;
use App\Services\SyntheticSearchService;

if (! function_exists('createMockAiService')) {
    /**
     * Create a mock AI service for testing.
     */
    function createMockAiService()
    {
        $mock = Mockery::mock(AiService::class);
        $mock->shouldReceive('prompt')->andReturn([
            'success' => true,
            'text' => 'Mock response',
            'model' => 'mock',
            'timestamp' => now()->toISOString(),
        ]);
        $mock->shouldReceive('promptWithFallback')->andReturn([
            'success' => true,
            'text' => 'Mock response',
            'model' => 'mock',
            'timestamp' => now()->toISOString(),
        ]);
        $mock->shouldReceive('isAvailable')->andReturn(false);

        return $mock;
    }
}

if (! function_exists('createMockSyntheticSearchService')) {
    /**
     * Create a mock Synthetic search service for testing.
     */
    function createMockSyntheticSearchService(array $results = [])
    {
        $mock = Mockery::mock(SyntheticSearchService::class);
        $mock->shouldReceive('search')->andReturn($results);
        $mock->shouldReceive('formatResults')->andReturn('Formatted search results');
        $mock->shouldReceive('compactResults')->andReturn($results);

        return $mock;
    }
}
