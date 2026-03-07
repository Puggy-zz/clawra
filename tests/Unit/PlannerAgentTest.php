<?php

declare(strict_types=1);

use App\Agents\PlannerAgent;

// Test that the planner agent can be instantiated
it('can be instantiated', function () {
    $agent = new PlannerAgent();
    expect($agent)->toBeInstanceOf(PlannerAgent::class);
});

// Test that the planner agent can create plans
it('can create plans', function () {
    $agent = new PlannerAgent();
    
    // This is a placeholder test since we don't have actual AI integration yet
    $result = $agent->createPlan('Test requirements');
    expect($result)->toBeArray();
});

// Test that the planner agent can breakdown features
it('can breakdown features', function () {
    $agent = new PlannerAgent();
    
    // This is a placeholder test since we don't have actual AI integration yet
    $result = $agent->breakdownFeature('Test feature');
    expect($result)->toBeArray();
});