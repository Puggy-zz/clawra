<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        // ── synthetic.new ─────────────────────────────────────────────
        // Primary inference provider. Subscription-based, 135 req / 5-hr rolling window per pack.
        // Quota endpoint: GET https://api.synthetic.new/v2/quotas (free, doesn't count against limit).
        $synthetic = Provider::query()->updateOrCreate(
            ['name' => 'synthetic'],
            [
                'vendor' => 'Synthetic',
                'type' => 'hybrid',
                'api_protocol' => 'Anthropic-compatible',
                'capability_tags' => ['chat', 'planning', 'reasoning', 'code-generation', 'web-search', 'embeddings'],
                'priority_preferences' => ['planning' => 1, 'reasoning' => 1, 'web-search' => 1, 'chat' => 2, 'default' => 5],
                'config' => [
                    'quotas_url' => 'https://api.synthetic.new/v2/quotas',
                    'search_url' => 'https://api.synthetic.new/v2/search',
                    'notes' => '$30/pack/month — 135 req/5-hr rolling window, 1 concurrent call per model per pack.',
                ],
                'usage_snapshot' => [],
                'rate_limits' => [
                    'requests_per_window' => 135,
                    'window_hours' => 5,
                    'window_type' => 'rolling',
                    'concurrency_per_model' => 1,
                ],
                'status' => 'active',
            ]
        );

        // synthetic.new via Laravel AI SDK (Anthropic-compatible endpoint)
        $syntheticLaravelRoute = $this->upsertRoute($synthetic, 'synthetic-laravel-ai', 'laravel_ai', 'api_key', [
            'usage_snapshot' => [
                'requests_used' => 0,
                'requests_remaining' => 135,
                'reset_at' => now()->addHours(5)->toISOString(),
            ],
            'rate_limits' => ['requests_per_window' => 135, 'window_hours' => 5, 'has_quotas_api' => true],
            'capability_tags' => ['chat', 'planning', 'reasoning', 'code-generation', 'web-search'],
            'supports_tools' => true,
            'supports_structured_output' => true,
            'priority' => 10,
            'status' => 'active',
        ]);

        // Models on synthetic-laravel-ai — one distinct model per agent to avoid concurrency conflicts.
        // Coordinator: DeepSeek-V3.2
        $this->upsertModel($syntheticLaravelRoute, 'deepseek-v3', 'hf:deepseek-ai/DeepSeek-V3.2', ['chat', 'reasoning'], true, 10, 131072);
        // Planner: Kimi-K2-Thinking (dedicated reasoning/planning model)
        $this->upsertModel($syntheticLaravelRoute, 'kimi-k2-thinking', 'hf:moonshotai/Kimi-K2-Thinking', ['planning', 'reasoning'], false, 20, 262144);
        // Reviewer: Qwen3-Coder
        $this->upsertModel($syntheticLaravelRoute, 'qwen3-coder', 'hf:Qwen/Qwen3-Coder-480B-A35B-Instruct', ['code-generation', 'reasoning'], false, 30, 262144);
        // Reviewer fallback / general fallback within synthetic
        $this->upsertModel($syntheticLaravelRoute, 'deepseek-r1', 'hf:deepseek-ai/DeepSeek-R1-0528', ['reasoning'], false, 40, 131072);

        // synthetic.new via opencode (for sandbox coding tasks)
        $syntheticOpenCodeRoute = $this->upsertRoute($synthetic, 'synthetic-opencode', 'opencode', 'api_key', [
            'usage_snapshot' => ['requests_used' => 0, 'requests_remaining' => 135, 'reset_at' => now()->addHours(5)->toISOString()],
            'rate_limits' => ['requests_per_window' => 135, 'window_hours' => 5, 'has_quotas_api' => true],
            'capability_tags' => ['chat', 'code-generation', 'planning'],
            'supports_tools' => true,
            'supports_structured_output' => false,
            'priority' => 20,
            'status' => 'active',
        ]);

        // Workhorse coder (default): Qwen3-Coder-480B
        $this->upsertModel($syntheticOpenCodeRoute, 'qwen3-coder', 'synthetic/hf:Qwen/Qwen3-Coder-480B-A35B-Instruct', ['code-generation', 'reasoning'], true, 10, 262144);
        // Planning / reasoning: Kimi-K2-Thinking
        $this->upsertModel($syntheticOpenCodeRoute, 'kimi-k2-thinking', 'synthetic/hf:moonshotai/Kimi-K2-Thinking', ['planning', 'reasoning'], false, 20, 262144);
        // Fast & cheap (discounted small model): GLM-4.7-Flash
        $this->upsertModel($syntheticOpenCodeRoute, 'glm-4.7-flash', 'synthetic/hf:zai-org/GLM-4.7-Flash', ['chat', 'code-generation'], false, 30, 131072);

        // ── Gemini (Google) ───────────────────────────────────────────
        // Free-tier fallback only. Single model to keep within free quota.
        // Rate limits tracked via response headers — no polling endpoint.
        $gemini = Provider::query()->updateOrCreate(
            ['name' => 'gemini'],
            [
                'vendor' => 'Google',
                'type' => 'api-only',
                'api_protocol' => 'native',
                'capability_tags' => ['chat', 'fallback'],
                'priority_preferences' => ['fallback' => 1, 'default' => 10],
                'config' => [
                    'notes' => 'Free tier only — rate limits tracked via response headers. Used as fallback for Coordinator and Researcher.',
                ],
                'usage_snapshot' => [],
                'rate_limits' => [
                    'requests_per_minute' => 15,
                    'window_type' => 'per-minute',
                ],
                'status' => 'active',
            ]
        );

        $geminiLaravelRoute = $this->upsertRoute($gemini, 'gemini-laravel-ai', 'laravel_ai', 'api_key', [
            'usage_snapshot' => [],
            'rate_limits' => ['requests_per_minute' => 15],
            'capability_tags' => ['chat', 'fallback'],
            'supports_tools' => true,
            'supports_structured_output' => true,
            'priority' => 30,
            'status' => 'active',
        ]);

        $this->upsertModel($geminiLaravelRoute, 'gemini-3.1-flash-lite-preview', 'gemini-3.1-flash-lite-preview', ['chat', 'fallback'], true, 10, 1048576);

        // Gemini opencode disabled — fallback use only, not needed for coding tasks.
        $this->upsertRoute($gemini, 'gemini-opencode', 'opencode', 'api_key', [
            'usage_snapshot' => [],
            'rate_limits' => ['requests_per_minute' => 15],
            'capability_tags' => ['chat'],
            'supports_tools' => true,
            'supports_structured_output' => false,
            'priority' => 40,
            'status' => 'disabled',
        ]);

        // ── OpenAI (ChatGPT Plus) ─────────────────────────────────────
        // Accessed through opencode via ChatGPT Plus subscription (OAuth).
        // Rate limits tracked via response headers — no polling endpoint.
        $openAi = Provider::query()->updateOrCreate(
            ['name' => 'openai'],
            [
                'vendor' => 'OpenAI',
                'type' => 'hybrid',
                'api_protocol' => 'OpenAI-compatible',
                'capability_tags' => ['chat', 'code-generation', 'reasoning'],
                'priority_preferences' => ['code-generation' => 1, 'reasoning' => 2, 'chat' => 3, 'default' => 8],
                'config' => [
                    'notes' => 'ChatGPT Plus via opencode OAuth. Rate limits tracked via response headers only.',
                ],
                'usage_snapshot' => [],
                'rate_limits' => [],
                'status' => 'active',
            ]
        );

        $openAiOpenCodeRoute = $this->upsertRoute($openAi, 'openai-opencode', 'opencode', 'chatgpt_oauth', [
            'usage_snapshot' => [],
            'rate_limits' => [],
            'capability_tags' => ['chat', 'code-generation', 'reasoning'],
            'supports_tools' => true,
            'supports_structured_output' => false,
            'priority' => 5,
            'status' => 'active',
        ]);

        $this->upsertModel($openAiOpenCodeRoute, 'gpt-5.3-codex', 'openai/gpt-5.3-codex', ['code-generation', 'reasoning'], false, 6, 128000);
        $this->upsertModel($openAiOpenCodeRoute, 'gpt-5.4', 'openai/gpt-5.4', ['chat', 'code-generation', 'reasoning'], false, 8, 128000);
        $this->upsertModel($openAiOpenCodeRoute, 'gpt-5-codex-mini', 'openai/gpt-5-codex-mini', ['code-generation'], false, 15, 128000);

        // OpenAI direct API (pay-per-token — disabled by default, available if needed)
        $this->upsertRoute($openAi, 'openai-laravel-ai', 'laravel_ai', 'api_key', [
            'usage_snapshot' => [],
            'rate_limits' => [],
            'capability_tags' => ['chat', 'reasoning'],
            'supports_tools' => true,
            'supports_structured_output' => true,
            'priority' => 100,
            'status' => 'disabled',
            'config' => ['notes' => 'Pay-per-token API key — disabled by default. Enable only if needed.'],
        ]);

        // ── Zen (OpenCode) ────────────────────────────────────────────
        // Free-tier fallback for opencode coding tasks. Curated models benchmarked
        // by the OpenCode team specifically for coding agents.
        // Auth: OPENCODE_API_KEY. Rate limits unknown — tracked via response headers.
        $zen = Provider::query()->updateOrCreate(
            ['name' => 'zen'],
            [
                'vendor' => 'OpenCode',
                'type' => 'api-only',
                'api_protocol' => 'OpenAI-compatible',
                'capability_tags' => ['chat', 'code-generation', 'fallback'],
                'priority_preferences' => ['code-generation' => 2, 'fallback' => 1, 'default' => 9],
                'config' => [
                    'notes' => 'Free tier via OpenCode Zen. Models curated and benchmarked by OpenCode for coding agents. Data may be used to improve models during free period.',
                ],
                'usage_snapshot' => [],
                'rate_limits' => [],
                'status' => 'active',
            ]
        );

        $zenOpenCodeRoute = $this->upsertRoute($zen, 'zen-opencode', 'opencode', 'api_key', [
            'usage_snapshot' => [],
            'rate_limits' => [],
            'capability_tags' => ['chat', 'code-generation', 'fallback'],
            'supports_tools' => true,
            'supports_structured_output' => false,
            'priority' => 15,
            'status' => 'active',
        ]);

        // MiniMax M2.5 Free: 80.2% SWE-bench Verified — top-tier coding, beats Opus 4.6
        $this->upsertModel($zenOpenCodeRoute, 'minimax-m2.5-free', 'zen/minimax-m2.5-free', ['code-generation', 'reasoning'], true, 10, 131072);
        // Big Pickle: OpenCode's own stealth model, purpose-built for coding agents
        $this->upsertModel($zenOpenCodeRoute, 'big-pickle', 'zen/big-pickle', ['code-generation'], false, 20, 131072);

        // ── Anthropic (Claude Max) ────────────────────────────────────
        // Accessed through opencode via Claude Max subscription (OAuth).
        // Rate limits tracked via response headers (anthropic-ratelimit-*).
        $anthropic = Provider::query()->updateOrCreate(
            ['name' => 'anthropic'],
            [
                'vendor' => 'Anthropic',
                'type' => 'hybrid',
                'api_protocol' => 'Anthropic-compatible',
                'capability_tags' => ['chat', 'code-generation', 'reasoning', 'long-context'],
                'priority_preferences' => ['code-generation' => 1, 'reasoning' => 1, 'chat' => 2, 'default' => 7],
                'config' => [
                    'notes' => 'Claude Max via opencode OAuth. Rate limits tracked via anthropic-ratelimit-* response headers.',
                ],
                'usage_snapshot' => [],
                'rate_limits' => [],
                'status' => 'active',
            ]
        );

        $anthropicOpenCodeRoute = $this->upsertRoute($anthropic, 'anthropic-opencode', 'opencode', 'provider_oauth', [
            'usage_snapshot' => [],
            'rate_limits' => [],
            'capability_tags' => ['chat', 'code-generation', 'reasoning', 'long-context'],
            'supports_tools' => true,
            'supports_structured_output' => false,
            'priority' => 5,
            'status' => 'active',
        ]);

        $this->upsertModel($anthropicOpenCodeRoute, 'claude-opus-4-6', 'anthropic/claude-opus-4-6', ['reasoning', 'code-generation', 'long-context'], false, 5, 200000);
        $this->upsertModel($anthropicOpenCodeRoute, 'claude-sonnet-4-6', 'anthropic/claude-sonnet-4-6', ['chat', 'code-generation'], true, 10, 200000);

        // Anthropic via Claude Code Agent SDK (Claude Max subscription, no API key needed)
        $anthropicClaudeCodeRoute = $this->upsertRoute($anthropic, 'anthropic-claude-code', 'claude_code', 'provider_oauth', [
            'usage_snapshot' => [],
            'rate_limits' => [],
            'capability_tags' => ['chat', 'code-generation', 'reasoning', 'long-context'],
            'supports_tools' => true,
            'supports_structured_output' => false,
            'priority' => 5,
            'status' => 'active',
            'config' => ['notes' => 'Claude Max subscription via Claude Code Agent SDK.'],
        ]);

        $this->upsertModel($anthropicClaudeCodeRoute, 'claude-opus-4-6', 'claude-opus-4-6', ['reasoning', 'code-generation', 'long-context'], false, 5, 200000);
        $this->upsertModel($anthropicClaudeCodeRoute, 'claude-sonnet-4-6', 'claude-sonnet-4-6', ['chat', 'code-generation'], true, 10, 200000);

        // Anthropic direct API (pay-per-token — disabled by default)
        $this->upsertRoute($anthropic, 'anthropic-laravel-ai', 'laravel_ai', 'api_key', [
            'usage_snapshot' => [],
            'rate_limits' => [],
            'capability_tags' => ['chat', 'reasoning'],
            'supports_tools' => true,
            'supports_structured_output' => true,
            'priority' => 100,
            'status' => 'disabled',
            'config' => ['notes' => 'Pay-per-token API key — disabled by default. Enable only if needed.'],
        ]);
    }

    protected function upsertRoute(Provider $provider, string $name, string $harness, string $authMode, array $attributes): ProviderRoute
    {
        return ProviderRoute::query()->updateOrCreate(
            ['name' => $name],
            [
                'provider_id' => $provider->id,
                'harness' => $harness,
                'auth_mode' => $authMode,
                'credential_type' => $authMode === 'api_key' ? 'api_key' : 'oauth_token',
                'usage_snapshot' => $attributes['usage_snapshot'] ?? [],
                'rate_limits' => $attributes['rate_limits'] ?? [],
                'capability_tags' => $attributes['capability_tags'] ?? [],
                'config' => $attributes['config'] ?? [],
                'supports_tools' => $attributes['supports_tools'] ?? false,
                'supports_structured_output' => $attributes['supports_structured_output'] ?? false,
                'priority' => $attributes['priority'] ?? 100,
                'status' => $attributes['status'] ?? 'active',
            ]
        );
    }

    protected function upsertModel(ProviderRoute $route, string $name, string $externalName, array $capabilities, bool $isDefault, int $priority, int $contextWindow): ProviderModel
    {
        return ProviderModel::query()->updateOrCreate(
            ['provider_route_id' => $route->id, 'name' => $name],
            [
                'external_name' => $externalName,
                'capabilities' => $capabilities,
                'config' => [],
                'context_window' => $contextWindow,
                'priority' => $priority,
                'is_default' => $isDefault,
                'status' => 'active',
            ]
        );
    }
}
