<?php

declare(strict_types=1);

namespace App\Ai\Providers;

use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Providers\AnthropicProvider;

class SyntheticProvider extends AnthropicProvider implements TextProvider
{
    /**
     * Get the name of the underlying AI provider.
     */
    public function driver(): string
    {
        return 'anthropic';
    }

    /**
     * Get the provider connection configuration other than the driver, key, and name.
     */
    public function additionalConfiguration(): array
    {
        return array_merge(parent::additionalConfiguration(), [
            'url' => $this->config['url'] ?? 'https://api.synthetic.new/anthropic/v1',
        ]);
    }

    /**
     * Get the name of the default text model.
     */
    public function defaultTextModel(): string
    {
        return $this->config['models']['text']['default'] ?? 'hf:deepseek-ai/DeepSeek-V3';
    }

    /**
     * Get the name of the cheapest text model.
     */
    public function cheapestTextModel(): string
    {
        return $this->config['models']['text']['cheapest'] ?? 'hf:deepseek-ai/DeepSeek-V3';
    }

    /**
     * Get the name of the smartest text model.
     */
    public function smartestTextModel(): string
    {
        return $this->config['models']['text']['smartest'] ?? 'hf:zai-org/GLM-4.7';
    }
}
