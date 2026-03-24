<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderModel extends Model
{
    /** @use HasFactory<\Database\Factories\ProviderModelFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'provider_route_id',
        'name',
        'external_name',
        'capabilities',
        'config',
        'context_window',
        'priority',
        'is_default',
        'active_requests',
        'max_concurrent_requests',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'config' => 'array',
            'context_window' => 'int',
            'priority' => 'int',
            'is_default' => 'bool',
            'active_requests' => 'int',
            'max_concurrent_requests' => 'int',
        ];
    }

    public function hasAvailableConcurrencySlot(): bool
    {
        return $this->active_requests < $this->max_concurrent_requests;
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(ProviderRoute::class, 'provider_route_id');
    }

    public function agentRuntimes(): HasMany
    {
        return $this->hasMany(AgentRuntime::class);
    }
}
