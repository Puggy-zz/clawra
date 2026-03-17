<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderRoute extends Model
{
    /** @use HasFactory<\Database\Factories\ProviderRouteFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'provider_id',
        'name',
        'harness',
        'auth_mode',
        'credential_type',
        'usage_snapshot',
        'rate_limits',
        'capability_tags',
        'config',
        'supports_tools',
        'supports_structured_output',
        'priority',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'usage_snapshot' => 'array',
            'rate_limits' => 'array',
            'capability_tags' => 'array',
            'config' => 'array',
            'supports_tools' => 'bool',
            'supports_structured_output' => 'bool',
            'priority' => 'int',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function models(): HasMany
    {
        return $this->hasMany(ProviderModel::class);
    }

    public function agentRuntimes(): HasMany
    {
        return $this->hasMany(AgentRuntime::class);
    }
}
