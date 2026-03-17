<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'project_conversation_id',
        'name',
        'description',
        'status',
        'workflow_id',
        'recommended_agent_id',
        'current_subtask_id',
    ];

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the workflow that the task belongs to.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ProjectConversation::class, 'project_conversation_id');
    }

    public function recommendedAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'recommended_agent_id');
    }

    /**
     * Get the current subtask for the task.
     */
    public function currentSubtask(): BelongsTo
    {
        return $this->belongsTo(Subtask::class, 'current_subtask_id');
    }

    /**
     * Get the subtasks for the task.
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class);
    }

    /**
     * Get the logs for the task.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(TaskLog::class);
    }

    /**
     * Get the review logs for the task.
     */
    public function reviewLogs(): HasMany
    {
        return $this->hasMany(ReviewLog::class);
    }
}
