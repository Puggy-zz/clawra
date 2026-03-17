<?php

declare(strict_types=1);

use App\Ai\Agents\PromptFallbackAgent;
use App\Models\Provider;
use App\Models\ProviderRoute;
use App\Services\AiService;
use App\Services\ProviderRegistry;

uses(Tests\TestCase::class);

function makeAiServiceForTests(): AiService
{
    $registry = Mockery::mock(ProviderRegistry::class);
    $registry->shouldReceive('getBestRouteForCapability')->andReturn(null);
    $registry->shouldReceive('getDefaultModelForRoute')->andReturn(null);
    $registry->shouldReceive('getRouteByName')->andReturn(null);
    $registry->shouldReceive('getProviderRouteForHarness')->andReturnUsing(function (string $providerName, string $harness = 'laravel_ai'): ?ProviderRoute {
        if ($harness !== 'laravel_ai' || ! in_array($providerName, ['synthetic', 'gemini'], true)) {
            return null;
        }

        $route = new ProviderRoute([
            'name' => $providerName,
            'harness' => $harness,
            'status' => 'active',
        ]);

        $route->setRelation('provider', new Provider(['name' => $providerName]));

        return $route;
    });
    $registry->shouldReceive('getRoutes')->andReturn(collect());
    $registry->shouldReceive('canUseRoute')->andReturnTrue();
    $registry->shouldReceive('recordRouteUsage')->andReturnTrue();

    return new AiService($registry);
}

it('resolves provider aliases to sdk provider and model pairs', function () {
    $service = makeAiServiceForTests();

    expect($service->resolveProviderAndModel('synthetic'))
        ->toMatchArray([
            'provider' => 'synthetic',
            'model' => null,
            'capability' => 'chat',
        ])
        ->and($service->resolveProviderAndModel('gemini'))
        ->toMatchArray([
            'provider' => 'gemini',
            'model' => null,
            'capability' => 'chat',
        ])
        ->and($service->resolveProviderAndModel('deepseek-v3'))
        ->toMatchArray([
            'provider' => 'synthetic',
            'model' => 'deepseek-v3',
            'capability' => 'chat',
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
    $registry = Mockery::mock(ProviderRegistry::class);
    $registry->shouldReceive('getRouteByName')->andReturn(null);
    $registry->shouldReceive('getRoutes')->andReturn(collect());
    $registry->shouldReceive('getDefaultModelForRoute')->andReturn(null);
    $registry->shouldReceive('getProviderRouteForHarness')->andReturnUsing(function (string $providerName, string $harness = 'laravel_ai'): ?ProviderRoute {
        if ($harness !== 'laravel_ai' || ! in_array($providerName, ['synthetic', 'gemini'], true)) {
            return null;
        }

        $route = new ProviderRoute([
            'name' => $providerName,
            'harness' => $harness,
            'status' => 'active',
        ]);

        $route->setRelation('provider', new Provider(['name' => $providerName]));

        return $route;
    });
    $registry->shouldReceive('canUseRoute')->andReturnTrue();

    $service = Mockery::mock(AiService::class, [$registry])->makePartial();
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
