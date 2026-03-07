<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'role',
        'description',
        'model',
        'fallback_model',
        'tools',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tools' => 'array',
        ];
    }

    /**
     * Get the subtasks handled by this agent.
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class);
    }

    /**
     * Get the task logs created by this agent.
     */
    public function taskLogs(): HasMany
    {
        return $this->hasMany(TaskLog::class);
    }

    /**
     * Get the review logs created by this agent.
     */
    public function reviewLogs(): HasMany
    {
        return $this->hasMany(ReviewLog::class);
    }
}
