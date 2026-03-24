<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentRuntime extends Model
{
    /** @use HasFactory<\Database\Factories\AgentRuntimeFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'agent_id',
        'provider_route_id',
        'provider_model_id',
        'fallback_provider_route_id',
        'fallback_provider_model_id',
        'name',
        'harness',
        'runtime_type',
        'runtime_ref',
        'description',
        'tools',
        'config',
        'is_default',
        'saves_documents',
        'sandboxed',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tools' => 'array',
            'config' => 'array',
            'is_default' => 'bool',
            'saves_documents' => 'bool',
            'sandboxed' => 'bool',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(ProviderRoute::class, 'provider_route_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(ProviderModel::class, 'provider_model_id');
    }

    public function fallbackRoute(): BelongsTo
    {
        return $this->belongsTo(ProviderRoute::class, 'fallback_provider_route_id');
    }

    public function fallbackModel(): BelongsTo
    {
        return $this->belongsTo(ProviderModel::class, 'fallback_provider_model_id');
    }
}
