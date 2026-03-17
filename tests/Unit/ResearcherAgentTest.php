<?php

declare(strict_types=1);

use App\Agents\ResearcherAgent;

// Test that the researcher agent can be instantiated
it('can be instantiated', function () {
    $agent = new ResearcherAgent(createMockAiService(), createMockSyntheticSearchService());
    expect($agent)->toBeInstanceOf(ResearcherAgent::class);
});

// Test that the researcher agent can conduct research
it('can conduct research', function () {
    $results = [[
        'title' => 'Synthetic Docs',
        'url' => 'https://synthetic.new/docs',
        'text' => 'Helpful content',
        'published' => '2025-11-05T00:00:00.000Z',
    ]];

    $agent = new ResearcherAgent(createMockAiService(), createMockSyntheticSearchService($results));

    $result = $agent->conductResearch('Test query');

    expect($result)->toBeArray();
    expect($result['query'])->toBe('Test query');
    expect($result['results'])->toBe($results);
    expect($result['summary'])->toBe('Mock response');
});

// Test that the researcher agent can summarize findings
it('can summarize findings', function () {
    $agent = new ResearcherAgent(createMockAiService(), createMockSyntheticSearchService());

    $result = $agent->summarizeFindings('Test content');
    expect($result)->toBeString();
});

// Test that the researcher agent can extract facts
it('can extract facts', function () {
    $agent = new ResearcherAgent(createMockAiService(), createMockSyntheticSearchService());

    $result = $agent->extractFacts('Test content');
    expect($result)->toBeArray();
});
