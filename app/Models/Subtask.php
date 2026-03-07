<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subtask extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_id',
        'agent_id',
        'name',
        'inputs',
        'outputs',
        'status',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inputs' => 'array',
            'outputs' => 'array',
        ];
    }

    /**
     * Get the task that owns the subtask.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the agent that handles the subtask.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get the logs for the subtask.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(TaskLog::class);
    }

    /**
     * Get the review logs for the subtask.
     */
    public function reviewLogs(): HasMany
    {
        return $this->hasMany(ReviewLog::class);
    }
}
