<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewLog extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_id',
        'subtask_id',
        'reviewer_agent_id',
        'decision',
        'diff_content',
        'comments',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the task that owns the review log.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the subtask that owns the review log.
     */
    public function subtask(): BelongsTo
    {
        return $this->belongsTo(Subtask::class);
    }

    /**
     * Get the agent that created the review log.
     */
    public function reviewerAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'reviewer_agent_id');
    }
}
