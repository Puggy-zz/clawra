<?php

declare(strict_types=1);

use App\Agents\PlannerAgent;
use App\Models\Agent;
use App\Services\AgentService;
use Database\Seeders\AgentSeeder;
use Database\Seeders\ProviderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Test that the planner agent can be instantiated
it('can be instantiated', function () {
    $agent = new PlannerAgent(createMockAiService());
    expect($agent)->toBeInstanceOf(PlannerAgent::class);
});

// Test that the planner agent can create plans
it('can create plans', function () {
    $agent = new PlannerAgent(createMockAiService());

    // This is a placeholder test since we don't have actual AI integration yet
    $result = $agent->createPlan('Test requirements');
    expect($result)->toBeArray();
});

// Test that the planner agent can breakdown features
it('can breakdown features', function () {
    $agent = new PlannerAgent(createMockAiService());

    // This is a placeholder test since we don't have actual AI integration yet
    $result = $agent->breakdownFeature('Test feature');
    expect($result)->toBeArray();
});

it('uses the planner model configuration stored in the database', function () {
    $this->seed([ProviderSeeder::class, AgentSeeder::class]);

    $aiService = Mockery::mock(\App\Services\AiService::class);
    $aiService->shouldReceive('promptWithFallback')
        ->once()
        ->with(Mockery::type('string'), 'hf:moonshotai/Kimi-K2-Thinking', 'hf:deepseek-ai/DeepSeek-V3.2')
        ->andReturn([
            'success' => true,
            'text' => "Configured summary\n- First action",
        ]);

    $agent = new PlannerAgent($aiService, app(AgentService::class));

    $result = $agent->createPlan('Use database configuration');

    expect($result['summary'])->toBe('Configured summary');
});
