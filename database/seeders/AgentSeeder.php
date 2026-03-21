<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Agents\CoordinatorAgent as CoordinatorRuntime;
use App\Agents\PlannerAgent as PlannerRuntime;
use App\Agents\ResearcherAgent as ResearcherRuntime;
use App\Agents\ReviewerAgent as ReviewerRuntime;
use App\Models\Agent;
use App\Models\AgentRuntime;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use Illuminate\Database\Seeder;

/**
 * Seeds the agent roster per PRD §5.3.
 *
 * Each agent is assigned a distinct primary model to avoid synthetic.new
 * concurrency conflicts (1 concurrent call per model per pack).
 *
 * | Agent                      | Primary model         | Route                    | Fallback                       |
 * |----------------------------|-----------------------|--------------------------|--------------------------------|
 * | Coordinator                | deepseek-v3           | synthetic-laravel-ai     | gemini-3.1-flash-lite-preview  |
 * | Planner                    | kimi-k2-thinking      | synthetic-laravel-ai     | deepseek-v3 (synth)            |
 * | Researcher                 | deepseek-v3           | synthetic-laravel-ai     | gemini-3.1-flash-lite-preview  |
 * | Reviewer                   | qwen3-coder           | synthetic-laravel-ai     | deepseek-r1 (synth)            |
 * | Developer (primary)        | qwen3-coder           | synthetic-opencode       | glm-4.7-flash (synth)          |
 * | Developer (claude-code)    | claude-sonnet-4-6     | anthropic-claude-code    | qwen3-coder (synthetic-opencode) |
 * | Test Writer (primary)      | glm-4.7-flash         | synthetic-opencode       | qwen3-coder (synth)            |
 * | Test Writer (claude-code)  | claude-sonnet-4-6     | anthropic-claude-code    | glm-4.7-flash (synthetic-opencode) |
 *
 * Researcher shares deepseek-v3 with Coordinator because the Researcher
 * primarily uses the /search endpoint (no model concurrency impact) and only
 * calls the model for summarisation — unlikely to conflict in practice.
 */
class AgentSeeder extends Seeder
{
    public function run(): void
    {
        // ── Coordinator (Clawra) ──────────────────────────────────────
        $clawra = Agent::query()->updateOrCreate(
            ['name' => 'Clawra'],
            [
                'role' => 'Coordinator',
                'description' => 'Routes requests, decomposes tasks, maintains project state, and manages inference-aware scheduling.',
                'status' => 'active',
                'tools' => ['coordination', 'project_state', 'provider_registry'],
            ]
        );

        $this->seedRuntime(
            agent: $clawra,
            harness: 'laravel_ai',
            name: 'coordinator-primary',
            runtimeType: 'laravel_class',
            runtimeRef: CoordinatorRuntime::class,
            routeName: 'synthetic-laravel-ai',
            modelName: 'deepseek-v3',
            fallbackRouteName: 'gemini-laravel-ai',
            fallbackModelName: 'gemini-3.1-flash-lite-preview',
            isDefault: true,
            tools: ['coordination', 'project_state', 'provider_registry'],
        );

        // ── Planner ───────────────────────────────────────────────────
        $planner = Agent::query()->updateOrCreate(
            ['name' => 'Planner'],
            [
                'role' => 'Planner',
                'description' => 'Project breakdown, spec writing, and implementation plan generation.',
                'status' => 'active',
                'tools' => ['task_planning', 'project_breakdown'],
            ]
        );

        $this->seedRuntime(
            agent: $planner,
            harness: 'laravel_ai',
            name: 'planner-primary',
            runtimeType: 'laravel_class',
            runtimeRef: PlannerRuntime::class,
            routeName: 'synthetic-laravel-ai',
            modelName: 'kimi-k2-thinking',
            fallbackRouteName: 'synthetic-laravel-ai',
            fallbackModelName: 'deepseek-v3',
            isDefault: true,
            tools: ['task_planning', 'project_breakdown'],
        );

        // ── Researcher ────────────────────────────────────────────────
        // Primary work is via synthetic.new /search endpoint (no model concurrency impact).
        // DeepSeek-V3-0324 is used for summarisation only — unlikely to conflict with Coordinator.
        $researcher = Agent::query()->updateOrCreate(
            ['name' => 'Researcher'],
            [
                'role' => 'Researcher',
                'description' => 'Web search, summarisation, and fact-finding via synthetic.new /search.',
                'status' => 'active',
                'tools' => ['web_search', 'summarisation'],
            ]
        );

        $this->seedRuntime(
            agent: $researcher,
            harness: 'laravel_ai',
            name: 'researcher-primary',
            runtimeType: 'laravel_class',
            runtimeRef: ResearcherRuntime::class,
            routeName: 'synthetic-laravel-ai',
            modelName: 'deepseek-v3',
            fallbackRouteName: 'gemini-laravel-ai',
            fallbackModelName: 'gemini-3.1-flash-lite-preview',
            isDefault: true,
            tools: ['web_search', 'summarisation'],
        );

        // ── Reviewer ──────────────────────────────────────────────────
        $reviewer = Agent::query()->updateOrCreate(
            ['name' => 'Reviewer'],
            [
                'role' => 'Reviewer',
                'description' => 'Code review, test validation, and spec compliance checks. Approves, requests changes, or escalates.',
                'status' => 'active',
                'tools' => ['code_review', 'test_runner'],
            ]
        );

        $this->seedRuntime(
            agent: $reviewer,
            harness: 'laravel_ai',
            name: 'reviewer-primary',
            runtimeType: 'laravel_class',
            runtimeRef: ReviewerRuntime::class,
            routeName: 'synthetic-laravel-ai',
            modelName: 'qwen3-coder',
            fallbackRouteName: 'synthetic-laravel-ai',
            fallbackModelName: 'deepseek-r1',
            isDefault: true,
            tools: ['code_review', 'test_runner'],
        );

        // ── Developer ─────────────────────────────────────────────────
        // Runs inside Docker Sandbox via opencode. Model-agnostic — opencode handles
        // model selection. Claude Max (Anthropic) is the primary; ChatGPT Plus (OpenAI) fallback.
        $developer = Agent::query()->updateOrCreate(
            ['name' => 'Developer'],
            [
                'role' => 'Developer',
                'description' => 'Code generation via opencode inside Docker Sandbox. Implements until tests pass.',
                'status' => 'active',
                'tools' => ['code_editor', 'terminal', 'test_runner'],
            ]
        );

        $this->seedRuntime(
            agent: $developer,
            harness: 'opencode',
            name: 'developer-primary',
            runtimeType: 'opencode_agent',
            runtimeRef: 'build',
            routeName: 'synthetic-opencode',
            modelName: 'qwen3-coder',
            fallbackRouteName: 'synthetic-opencode',
            fallbackModelName: 'glm-4.7-flash',
            isDefault: true,
            tools: ['code_editor', 'terminal', 'test_runner'],
        );

        $this->seedRuntime(
            agent: $developer,
            harness: 'claude_code',
            name: 'developer-claude-code',
            runtimeType: 'claude_code_agent',
            runtimeRef: 'claude-code-runner',
            routeName: 'anthropic-claude-code',
            modelName: 'claude-sonnet-4-6',
            fallbackRouteName: 'synthetic-opencode',
            fallbackModelName: 'qwen3-coder',
            isDefault: false,
            tools: ['code_editor', 'terminal', 'test_runner'],
        );

        // ── Test Writer ───────────────────────────────────────────────
        // Runs inside Docker Sandbox via opencode. Writes failing Pest tests before implementation.
        $testWriter = Agent::query()->updateOrCreate(
            ['name' => 'Test Writer'],
            [
                'role' => 'Test Writer',
                'description' => 'Writes failing Pest tests against spec and acceptance criteria. Phase 1 TDD workflow.',
                'status' => 'active',
                'tools' => ['code_editor', 'test_runner'],
            ]
        );

        $this->seedRuntime(
            agent: $testWriter,
            harness: 'opencode',
            name: 'test-writer-primary',
            runtimeType: 'opencode_agent',
            runtimeRef: 'build',
            routeName: 'synthetic-opencode',
            modelName: 'glm-4.7-flash',
            fallbackRouteName: 'synthetic-opencode',
            fallbackModelName: 'qwen3-coder',
            isDefault: true,
            tools: ['code_editor', 'test_runner'],
        );

        $this->seedRuntime(
            agent: $testWriter,
            harness: 'claude_code',
            name: 'test-writer-claude-code',
            runtimeType: 'claude_code_agent',
            runtimeRef: 'claude-code-runner',
            routeName: 'anthropic-claude-code',
            modelName: 'claude-sonnet-4-6',
            fallbackRouteName: 'synthetic-opencode',
            fallbackModelName: 'glm-4.7-flash',
            isDefault: false,
            tools: ['code_editor', 'test_runner'],
        );
    }

    protected function seedRuntime(
        Agent $agent,
        string $harness,
        string $name,
        string $runtimeType,
        string $runtimeRef,
        ?string $routeName,
        ?string $modelName,
        ?string $fallbackRouteName,
        ?string $fallbackModelName,
        bool $isDefault,
        array $tools,
    ): AgentRuntime {
        $route = $routeName ? ProviderRoute::query()->where('name', $routeName)->first() : null;
        $model = ($route && $modelName)
            ? ProviderModel::query()->where('provider_route_id', $route->id)->where('name', $modelName)->first()
            : null;

        $fallbackRoute = $fallbackRouteName ? ProviderRoute::query()->where('name', $fallbackRouteName)->first() : null;
        $fallbackModel = ($fallbackRoute && $fallbackModelName)
            ? ProviderModel::query()->where('provider_route_id', $fallbackRoute->id)->where('name', $fallbackModelName)->first()
            : null;

        return AgentRuntime::query()->updateOrCreate(
            ['agent_id' => $agent->id, 'harness' => $harness, 'name' => $name],
            [
                'provider_route_id' => $route?->id,
                'provider_model_id' => $model?->id,
                'fallback_provider_route_id' => $fallbackRoute?->id,
                'fallback_provider_model_id' => $fallbackModel?->id,
                'runtime_type' => $runtimeType,
                'runtime_ref' => $runtimeRef,
                'description' => sprintf('%s runtime on %s.', $agent->name, $harness),
                'tools' => $tools,
                'config' => [],
                'is_default' => $isDefault,
                'status' => 'active',
            ]
        );
    }
}
