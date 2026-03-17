<?php

declare(strict_types=1);

use App\Ai\Agents\PromptFallbackAgent;
use App\Services\AiService;
use App\Services\ProviderRegistry;

uses(Tests\TestCase::class);

function makeAiServiceForTests(): AiService
{
    $registry = Mockery::mock(ProviderRegistry::class);
    $registry->shouldReceive('getActiveProvider')->andReturn(null);
    $registry->shouldReceive('getProviders')->andReturn(collect());
    $registry->shouldReceive('getBestProviderForCapability')->andReturn(null);

    return new AiService($registry);
}

it('resolves provider aliases to sdk provider and model pairs', function () {
    $service = makeAiServiceForTests();

    expect($service->resolveProviderAndModel('synthetic'))->toBe([
        'provider' => 'synthetic',
        'model' => null,
    ])->and($service->resolveProviderAndModel('gemini'))->toBe([
        'provider' => 'gemini',
        'model' => null,
    ])->and($service->resolveProviderAndModel('deepseek-v3'))->toBe([
        'provider' => 'synthetic',
        'model' => 'hf:deepseek-ai/DeepSeek-V3',
    ]);
});

it('prompts through the sdk without using placeholder responses', function () {
    PromptFallbackAgent::fake(['Real sdk-backed response'])->preventStrayPrompts();

    $service = makeAiServiceForTests();
    $response = $service->prompt('Say hello', 'synthetic');

    expect($response['success'])->toBeTrue()
        ->and($response['text'])->toBe('Real sdk-backed response');

    PromptFallbackAgent::assertPrompted('Say hello');
});

it('falls back to gemini when the primary provider fails', function () {
    $service = Mockery::mock(AiService::class, [Mockery::mock(ProviderRegistry::class)])->makePartial();
    $service->shouldReceive('prompt')
        ->once()
        ->with('Need a fallback', 'synthetic')
        ->andReturn([
            'success' => false,
            'error' => 'Synthetic rate limit reached',
        ]);
    $service->shouldReceive('prompt')
        ->once()
        ->with('Need a fallback', 'gemini')
        ->andReturn([
            'success' => true,
            'text' => 'Gemini fallback response',
        ]);

    $response = $service->promptWithFallback('Need a fallback', 'synthetic', 'gemini');

    expect($response['success'])->toBeTrue()
        ->and($response['text'])->toBe('Gemini fallback response');
});
