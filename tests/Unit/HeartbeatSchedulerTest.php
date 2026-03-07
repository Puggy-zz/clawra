<?php

declare(strict_types=1);

use App\Services\HeartbeatScheduler;
use App\Services\ProviderRegistry;
use App\Models\HeartbeatLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

// Test that the heartbeat scheduler can be instantiated
it('can be instantiated', function () {
    $providerRegistry = mock(ProviderRegistry::class);
    $scheduler = new HeartbeatScheduler($providerRegistry);
    expect($scheduler)->toBeInstanceOf(HeartbeatScheduler::class);
});

// Test that the heartbeat scheduler can execute
it('can execute', function () {
    $providerRegistry = mock(ProviderRegistry::class);
    $providerRegistry->shouldReceive('getActiveProviders')->andReturn(collect());
    
    $scheduler = new HeartbeatScheduler($providerRegistry);
    
    // This should not throw an exception
    $scheduler->execute();
    
    // Check that a heartbeat log was created
    expect(HeartbeatLog::count())->toBe(1);
    
    $log = HeartbeatLog::first();
    expect($log->decisions)->toBeArray();
    expect($log->tasks_queued)->toBeArray();
    expect($log->provider_status)->toBeArray();
});