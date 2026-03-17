<?php

declare(strict_types=1);

use App\Models\Provider;
use App\Models\ProviderRoute;
use App\Services\ProviderRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('can be instantiated', function () {
    expect(new ProviderRegistry)->toBeInstanceOf(ProviderRegistry::class);
});

it('selects the best laravel ai route for a capability', function () {
    $synthetic = Provider::factory()->create([
        'name' => 'synthetic',
        'priority_preferences' => ['planning' => 1, 'default' => 5],
        'capability_tags' => ['planning'],
        'status' => 'active',
    ]);

    $gemini = Provider::factory()->create([
        'name' => 'gemini',
        'priority_preferences' => ['planning' => 3, 'default' => 10],
        'capability_tags' => ['planning'],
        'status' => 'active',
    ]);

    ProviderRoute::factory()->create([
        'provider_id' => $synthetic->id,
        'name' => 'synthetic-laravel-ai',
        'harness' => 'laravel_ai',
        'auth_mode' => 'api_key',
        'capability_tags' => ['planning'],
        'priority' => 10,
        'usage_snapshot' => ['requests_remaining' => 20, 'reset_at' => now()->addHour()->toISOString()],
        'status' => 'active',
    ]);

    ProviderRoute::factory()->create([
        'provider_id' => $gemini->id,
        'name' => 'gemini-laravel-ai',
        'harness' => 'laravel_ai',
        'auth_mode' => 'api_key',
        'capability_tags' => ['planning'],
        'priority' => 30,
        'usage_snapshot' => ['requests_remaining' => 100, 'reset_at' => null],
        'status' => 'active',
    ]);

    $registry = new ProviderRegistry;

    expect($registry->getBestRouteForCapability('planning', harness: 'laravel_ai')?->name)->toBe('synthetic-laravel-ai');
});

it('treats exhausted routes as rate limited until reset', function () {
    $provider = Provider::factory()->create(['name' => 'synthetic', 'status' => 'active']);

    ProviderRoute::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'synthetic-laravel-ai',
        'harness' => 'laravel_ai',
        'auth_mode' => 'api_key',
        'capability_tags' => ['chat'],
        'usage_snapshot' => ['requests_remaining' => 0, 'reset_at' => now()->addHours(2)->toISOString()],
        'status' => 'active',
    ]);

    $registry = new ProviderRegistry;

    expect($registry->isProviderRateLimited('synthetic'))->toBeTrue()
        ->and($registry->canUseProvider('synthetic'))->toBeFalse();
});

it('records route usage and decrements remaining requests', function () {
    $provider = Provider::factory()->create(['name' => 'synthetic', 'status' => 'active']);

    $route = ProviderRoute::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'synthetic-laravel-ai',
        'harness' => 'laravel_ai',
        'auth_mode' => 'api_key',
        'capability_tags' => ['chat'],
        'rate_limits' => ['requests_per_window' => 5, 'window_hours' => 5],
        'usage_snapshot' => ['requests_used' => 1, 'requests_remaining' => 4, 'reset_at' => now()->addHours(5)->toISOString()],
        'status' => 'active',
    ]);

    $registry = new ProviderRegistry;

    expect($registry->recordRouteUsage($route, 'hf:deepseek-ai/DeepSeek-V3'))->toBeTrue();

    $freshRoute = ProviderRoute::query()->where('name', 'synthetic-laravel-ai')->firstOrFail();

    expect($freshRoute->usage_snapshot['requests_used'])->toBe(2)
        ->and($freshRoute->usage_snapshot['requests_remaining'])->toBe(3)
        ->and($freshRoute->usage_snapshot['current_model'])->toBe('hf:deepseek-ai/DeepSeek-V3');
});

it('returns a fallback provider name for a capability on the same harness', function () {
    $synthetic = Provider::factory()->create([
        'name' => 'synthetic',
        'priority_preferences' => ['chat' => 1],
        'status' => 'active',
    ]);

    $gemini = Provider::factory()->create([
        'name' => 'gemini',
        'priority_preferences' => ['chat' => 2],
        'status' => 'active',
    ]);

    ProviderRoute::factory()->create([
        'provider_id' => $synthetic->id,
        'name' => 'synthetic-laravel-ai',
        'harness' => 'laravel_ai',
        'auth_mode' => 'api_key',
        'capability_tags' => ['chat'],
        'usage_snapshot' => ['requests_remaining' => 0, 'reset_at' => now()->addHours(5)->toISOString()],
        'status' => 'rate-limited',
    ]);

    ProviderRoute::factory()->create([
        'provider_id' => $gemini->id,
        'name' => 'gemini-laravel-ai',
        'harness' => 'laravel_ai',
        'auth_mode' => 'api_key',
        'capability_tags' => ['chat', 'fallback'],
        'usage_snapshot' => ['requests_remaining' => 1000, 'reset_at' => null],
        'status' => 'active',
    ]);

    $registry = new ProviderRegistry;

    expect($registry->getFallbackProviderName('synthetic', 'chat', 'laravel_ai'))->toBe('gemini');
});
