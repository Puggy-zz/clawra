<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\ResearchService;

test('instantiates research service successfully', function () {
    $service = new ResearchService;

    expect($service)->toBeInstanceOf(ResearchService::class);
});

test('conducts basic research and returns structured output', function () {
    $query = 'Test research topic for basic check';

    $service = new ResearchService;

    $result = $service->conductDeepResearch($query);

    expect($result)->toBeArray();
    expect($result)->toHaveKey('success');
    expect($result['output'])->toBeString();
    expect($result['findings'])->toBeString();
});

test('handles comprehensive research depth properly', function () {
    $query = 'Laravel 12 frameworks';
    $context = 'Core framework details';

    $service = new ResearchService;

    $result = $service->conductDeepResearch($query, $context, 'basic');

    expect($result)->toHaveKey('success');
});
