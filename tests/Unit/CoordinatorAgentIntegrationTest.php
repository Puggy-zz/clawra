<?php

declare(strict_types=1);

use App\Agents\CoordinatorAgent;
use App\Services\AiService;
use App\Services\ProviderRegistry;

// Test that the coordinator agent can process messages with real AI
it('can process messages with real AI', function () {
    // Skip this test if we're not in a CI environment or if AI is not configured
    if (! env('CI') && ! env('TEST_WITH_AI', false)) {
        $this->markTestSkipped('AI integration tests are disabled. Set TEST_WITH_AI=true to enable.');
    }

    $providerRegistry = new ProviderRegistry;
    $aiService = new AiService($providerRegistry);
    $agent = new CoordinatorAgent($aiService);

    $result = $agent->processMessage('Hello, what can you do?');

    expect($result)->toBeString();
    expect(strlen($result))->toBeGreaterThan(0);
    expect($result)->not->toContain('placeholder');
    expect($result)->not->toContain('AI services are not currently available');
});

// Test that the coordinator agent can decompose requests with real AI
it('can decompose requests with real AI', function () {
    // Skip this test if we're not in a CI environment or if AI is not configured
    if (! env('CI') && ! env('TEST_WITH_AI', false)) {
        $this->markTestSkipped('AI integration tests are disabled. Set TEST_WITH_AI=true to enable.');
    }

    $providerRegistry = new ProviderRegistry;
    $aiService = new AiService($providerRegistry);
    $agent = new CoordinatorAgent($aiService);

    $result = $agent->decomposeRequest('Create a simple Laravel application that manages tasks');

    expect($result)->toBeArray();
    expect(count($result))->toBeGreaterThan(0);

    // Check that the result contains task objects with proper structure
    $firstTask = $result[0];
    expect($firstTask)->toBeArray();
    expect($firstTask)->toHaveKey('type');
    expect($firstTask)->toHaveKey('description');
    expect($firstTask)->toHaveKey('priority');
});
