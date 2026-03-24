<?php

declare(strict_types=1);

use App\Models\Provider;
use App\Models\ProviderRoute;
use App\Services\RateLimitRecoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('can be instantiated', function () {
    expect(new RateLimitRecoveryService)->toBeInstanceOf(RateLimitRecoveryService::class);
});

it('recovers routes whose reset_at has passed', function () {
    $provider = Provider::factory()->create(['name' => 'synthetic', 'status' => 'active']);

    $route = ProviderRoute::factory()->create([
        'provider_id' => $provider->id,
        'status' => 'rate-limited',
        'usage_snapshot' => [
            'requests_remaining' => 0,
            'reset_at' => now()->subMinutes(10)->toISOString(),
        ],
        'rate_limits' => ['requests_per_window' => 100],
    ]);

    $service = new RateLimitRecoveryService;
    $recovered = $service->recoverExpiredWindows();

    expect($recovered)->toHaveCount(1)
        ->and($recovered[0]['route'])->toBe($route->name)
        ->and($route->refresh()->status)->toBe('active')
        ->and($route->refresh()->usage_snapshot['requests_remaining'])->toBe(100);
});

it('does not recover routes whose reset_at is still in the future', function () {
    $provider = Provider::factory()->create(['name' => 'synthetic', 'status' => 'active']);

    ProviderRoute::factory()->create([
        'provider_id' => $provider->id,
        'status' => 'rate-limited',
        'usage_snapshot' => [
            'requests_remaining' => 0,
            'reset_at' => now()->addHours(2)->toISOString(),
        ],
    ]);

    $service = new RateLimitRecoveryService;
    $recovered = $service->recoverExpiredWindows();

    expect($recovered)->toBeEmpty();
});

it('clears weekly counters and advances weekly_reset_at', function () {
    $provider = Provider::factory()->create(['name' => 'synthetic', 'status' => 'active']);

    $route = ProviderRoute::factory()->create([
        'provider_id' => $provider->id,
        'status' => 'active',
        'usage_snapshot' => [
            'weekly_requests_used' => 500,
            'weekly_requests_remaining' => 0,
        ],
        'rate_limits' => ['weekly_requests_limit' => 1000],
    ]);

    $service = new RateLimitRecoveryService;
    $service->clearWeeklyCounters($route);

    $snapshot = $route->refresh()->usage_snapshot;

    expect($snapshot['weekly_requests_used'])->toBe(0)
        ->and($snapshot['weekly_requests_remaining'])->toBe(1000)
        ->and($snapshot['weekly_reset_at'])->not->toBeNull();
});
