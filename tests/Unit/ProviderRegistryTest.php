<?php

declare(strict_types=1);

use App\Services\ProviderRegistry;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

// Test that the provider registry can be instantiated
it('can be instantiated', function () {
    $registry = new ProviderRegistry();
    expect($registry)->toBeInstanceOf(ProviderRegistry::class);
});

// Test that the provider registry can get active providers
it('can get active providers', function () {
    // Create some test providers
    Provider::factory()->count(3)->create(['status' => 'active']);
    Provider::factory()->count(2)->create(['status' => 'inactive']);
    
    $registry = new ProviderRegistry();
    $providers = $registry->getActiveProviders();
    
    expect($providers)->toHaveCount(3);
    foreach ($providers as $provider) {
        expect($provider->status)->toBe('active');
    }
});

// Test that the provider registry can get a provider by name
it('can get a provider by name', function () {
    $provider = Provider::factory()->create(['name' => 'test-provider']);
    
    $registry = new ProviderRegistry();
    $foundProvider = $registry->getProvider('test-provider');
    
    expect($foundProvider)->not()->toBeNull();
    expect($foundProvider->name)->toBe('test-provider');
});

// Test that the provider registry returns null for non-existent providers
it('returns null for non-existent providers', function () {
    $registry = new ProviderRegistry();
    $provider = $registry->getProvider('non-existent-provider');
    
    expect($provider)->toBeNull();
});

// Test that the provider registry can update usage snapshots
it('can update usage snapshots', function () {
    $provider = Provider::factory()->create(['name' => 'test-provider']);
    
    $registry = new ProviderRegistry();
    $usageData = ['requests_used' => 10, 'requests_remaining' => 90];
    
    $result = $registry->updateUsageSnapshot('test-provider', $usageData);
    
    expect($result)->toBeTrue();
    
    $updatedProvider = $registry->getProvider('test-provider');
    expect($updatedProvider->usage_snapshot)->toBe($usageData);
});

// Test that the provider registry can update rate limits
it('can update rate limits', function () {
    $provider = Provider::factory()->create(['name' => 'test-provider']);
    
    $registry = new ProviderRegistry();
    $rateLimits = ['requests_per_window' => 100, 'window_minutes' => 300];
    
    $result = $registry->updateRateLimits('test-provider', $rateLimits);
    
    expect($result)->toBeTrue();
    
    $updatedProvider = $registry->getProvider('test-provider');
    expect($updatedProvider->rate_limits)->toBe($rateLimits);
});

// Test that the provider registry can check if a provider is rate limited
it('can check if a provider is rate limited', function () {
    Provider::factory()->create(['name' => 'rate-limited-provider', 'status' => 'rate-limited']);
    Provider::factory()->create(['name' => 'active-provider', 'status' => 'active']);
    
    $registry = new ProviderRegistry();
    
    expect($registry->isProviderRateLimited('rate-limited-provider'))->toBeTrue();
    expect($registry->isProviderRateLimited('active-provider'))->toBeFalse();
    expect($registry->isProviderRateLimited('non-existent-provider'))->toBeTrue();
});