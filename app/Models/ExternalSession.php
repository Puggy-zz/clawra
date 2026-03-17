<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'project_conversation_id',
        'task_id',
        'subtask_id',
        'agent_id',
        'agent_runtime_id',
        'harness',
        'external_id',
        'status',
        'title',
        'metadata',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_seen_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ProjectConversation::class, 'project_conversation_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function subtask(): BelongsTo
    {
        return $this->belongsTo(Subtask::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function agentRuntime(): BelongsTo
    {
        return $this->belongsTo(AgentRuntime::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ExternalSessionEvent::class);
    }

    public function processLogs(): HasMany
    {
        return $this->hasMany(ProcessLog::class);
    }
}
