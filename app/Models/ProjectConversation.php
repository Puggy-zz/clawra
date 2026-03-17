<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'purpose',
        'status',
        'is_default',
        'laravel_ai_conversation_id',
        'state_document',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'bool',
            'state_document' => 'array',
            'last_message_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function processLogs(): HasMany
    {
        return $this->hasMany(ProcessLog::class);
    }

    public function externalSessions(): HasMany
    {
        return $this->hasMany(ExternalSession::class);
    }
}
