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
        $synthetic = Provider::query()->updateOrCreate(
            ['name' => 'synthetic'],
            [
                'vendor' => 'Synthetic',
                'type' => 'hybrid',
                'api_protocol' => 'Anthropic-compatible',
                'usage_snapshot' => ['notes' => 'See route-level snapshots for execution state.'],
                'rate_limits' => ['notes' => 'See route-level limits for execution state.'],
                'capability_tags' => ['chat', 'planning', 'reasoning', 'web-search'],
                'priority_preferences' => ['planning' => 1, 'reasoning' => 1, 'web-search' => 1, 'chat' => 2, 'default' => 5],
                'config' => ['supports' => ['api_key'], 'notes' => 'Subscription account with API key support.'],
                'status' => 'active',
            ]
        );

        $gemini = Provider::query()->updateOrCreate(
            ['name' => 'gemini'],
            [
                'vendor' => 'Google',
                'type' => 'api-only',
                'api_protocol' => 'native',
                'usage_snapshot' => ['notes' => 'See route-level snapshots for execution state.'],
                'rate_limits' => ['notes' => 'See route-level limits for execution state.'],
                'capability_tags' => ['chat', 'planning', 'fallback'],
                'priority_preferences' => ['fallback' => 1, 'chat' => 2, 'planning' => 3, 'default' => 10],
                'config' => ['supports' => ['api_key']],
                'status' => 'active',
            ]
        );

        $openAi = Provider::query()->updateOrCreate(
            ['name' => 'openai'],
            [
                'vendor' => 'OpenAI',
                'type' => 'hybrid',
                'api_protocol' => 'OpenAI-compatible',
                'usage_snapshot' => ['notes' => 'OAuth subscription-backed routes are tracked per harness.'],
                'rate_limits' => ['notes' => 'Rate limits differ by ChatGPT subscription vs API key.'],
                'capability_tags' => ['chat', 'coding', 'reasoning'],
                'priority_preferences' => ['coding' => 1, 'reasoning' => 2, 'chat' => 3, 'default' => 8],
                'config' => ['supports' => ['chatgpt_oauth', 'api_key']],
                'status' => 'active',
            ]
        );

        $zen = Provider::query()->updateOrCreate(
            ['name' => 'zen'],
            [
                'vendor' => 'OpenCode',
                'type' => 'subscription',
                'api_protocol' => 'native',
                'usage_snapshot' => ['notes' => 'Zen is only available through OpenCode.'],
                'rate_limits' => ['notes' => 'Managed through OpenCode account limits.'],
                'capability_tags' => ['chat', 'coding'],
                'priority_preferences' => ['coding' => 1, 'chat' => 2, 'default' => 20],
                'config' => ['supports' => ['provider_oauth']],
                'status' => 'active',
            ]
        );

        $syntheticLaravelRoute = $this->upsertRoute($synthetic, 'synthetic-laravel-ai', 'laravel_ai', 'api_key', [
            'usage_snapshot' => [
                'requests_used' => 0,
                'requests_remaining' => 135,
                'reset_at' => now()->addHours(5)->toISOString(),
            ],
            'rate_limits' => [
                'requests_per_window' => 135,
                'window_hours' => 5,
            ],
            'capability_tags' => ['chat', 'planning', 'reasoning', 'web-search'],
            'supports_tools' => true,
            'supports_structured_output' => true,
            'priority' => 10,
            'status' => 'active',
        ]);

        $this->upsertModel($syntheticLaravelRoute, 'deepseek-v3', 'hf:deepseek-ai/DeepSeek-V3', ['chat', 'reasoning'], true, 10, 131072);
        $this->upsertModel($syntheticLaravelRoute, 'kimi-k2-instruct', 'hf:moonshotai/Kimi-K2-Instruct-0905', ['planning'], false, 20, 131072);
        $this->upsertModel($syntheticLaravelRoute, 'glm-4.7', 'hf:zai-org/GLM-4.7', ['reasoning'], false, 30, 131072);

        $syntheticOpenCodeRoute = $this->upsertRoute($synthetic, 'synthetic-opencode', 'opencode', 'api_key', [
            'usage_snapshot' => ['requests_remaining' => 135, 'reset_at' => now()->addHours(5)->toISOString()],
            'rate_limits' => ['requests_per_window' => 135, 'window_hours' => 5],
            'capability_tags' => ['chat', 'coding', 'planning'],
            'supports_tools' => true,
            'supports_structured_output' => false,
            'priority' => 20,
            'status' => 'active',
        ]);

        $this->upsertModel($syntheticOpenCodeRoute, 'deepseek-v3', 'synthetic/deepseek-v3', ['chat', 'coding'], true, 10, 131072);
        $this->upsertModel($syntheticOpenCodeRoute, 'kimi-k2-instruct', 'synthetic/kimi-k2-instruct', ['planning'], false, 20, 131072);

        $geminiLaravelRoute = $this->upsertRoute($gemini, 'gemini-laravel-ai', 'laravel_ai', 'api_key', [
            'usage_snapshot' => ['requests_remaining' => 100000, 'reset_at' => null],
            'rate_limits' => ['requests_per_window' => null, 'window_hours' => null],
            'capability_tags' => ['chat', 'planning', 'fallback'],
            'supports_tools' => true,
            'supports_structured_output' => true,
            'priority' => 30,
            'status' => 'active',
        ]);

        $this->upsertModel($geminiLaravelRoute, 'gemini-2.5-pro', 'gemini-2.5-pro', ['chat', 'planning'], true, 10, 1048576);
        $this->upsertModel($geminiLaravelRoute, 'gemini-2.5-flash', 'gemini-2.5-flash', ['chat'], false, 20, 1048576);

        $geminiOpenCodeRoute = $this->upsertRoute($gemini, 'gemini-opencode', 'opencode', 'api_key', [
            'usage_snapshot' => ['requests_remaining' => 100000, 'reset_at' => null],
            'rate_limits' => ['requests_per_window' => null, 'window_hours' => null],
            'capability_tags' => ['chat', 'coding', 'planning'],
            'supports_tools' => true,
            'supports_structured_output' => false,
            'priority' => 40,
            'status' => 'active',
        ]);

        $this->upsertModel($geminiOpenCodeRoute, 'gemini-2.5-pro', 'google/gemini-2.5-pro', ['chat', 'coding'], true, 10, 1048576);

        $openAiOpenCodeRoute = $this->upsertRoute($openAi, 'openai-opencode-chatgpt', 'opencode', 'chatgpt_oauth', [
            'usage_snapshot' => ['requests_remaining' => 80, 'reset_at' => now()->addHours(3)->toISOString()],
            'rate_limits' => ['requests_per_window' => 80, 'window_hours' => 3],
            'capability_tags' => ['chat', 'coding', 'reasoning'],
            'supports_tools' => true,
            'supports_structured_output' => false,
            'priority' => 5,
            'status' => 'active',
        ]);

        $this->upsertModel($openAiOpenCodeRoute, 'gpt-5.4', 'openai/gpt-5.4', ['chat', 'coding', 'reasoning'], true, 10, 400000);

        $openAiCodexRoute = $this->upsertRoute($openAi, 'openai-codex-chatgpt', 'codex', 'chatgpt_oauth', [
            'usage_snapshot' => ['requests_remaining' => 60, 'reset_at' => now()->addHours(3)->toISOString()],
            'rate_limits' => ['requests_per_window' => 60, 'window_hours' => 3],
            'capability_tags' => ['coding', 'reasoning'],
            'supports_tools' => true,
            'supports_structured_output' => false,
            'priority' => 15,
            'status' => 'active',
        ]);

        $this->upsertModel($openAiCodexRoute, 'gpt-5.4', 'gpt-5.4', ['coding', 'reasoning'], true, 10, 400000);
        $this->upsertModel($openAiCodexRoute, 'gpt-5.3-codex', 'gpt-5.3-codex', ['coding'], false, 20, 400000);

        $this->upsertRoute($openAi, 'openai-laravel-ai-api', 'laravel_ai', 'api_key', [
            'usage_snapshot' => ['requests_remaining' => null, 'reset_at' => null],
            'rate_limits' => ['requests_per_window' => null, 'window_hours' => null],
            'capability_tags' => ['chat', 'reasoning'],
            'supports_tools' => true,
            'supports_structured_output' => true,
            'priority' => 100,
            'status' => 'disabled',
            'config' => ['notes' => 'Reserved for future pay-per-token API key access.'],
        ]);

        $zenOpenCodeRoute = $this->upsertRoute($zen, 'zen-opencode', 'opencode', 'provider_oauth', [
            'usage_snapshot' => ['requests_remaining' => 50, 'reset_at' => now()->addHours(2)->toISOString()],
            'rate_limits' => ['requests_per_window' => 50, 'window_hours' => 2],
            'capability_tags' => ['chat', 'coding'],
            'supports_tools' => true,
            'supports_structured_output' => false,
            'priority' => 25,
            'status' => 'active',
        ]);

        $this->upsertModel($zenOpenCodeRoute, 'zen-default', 'opencode/zen-default', ['chat', 'coding'], true, 10, 200000);
    }

    protected function upsertRoute(Provider $provider, string $name, string $harness, string $authMode, array $attributes): ProviderRoute
    {
        return ProviderRoute::query()->updateOrCreate(
            [
                'provider_id' => $provider->id,
                'harness' => $harness,
                'auth_mode' => $authMode,
            ],
            [
                'name' => $name,
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
            [
                'provider_route_id' => $route->id,
                'name' => $name,
            ],
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
