<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sandbox extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'task_id',
        'name',
        'status',
        'path',
        'sandbox_id',
        'image',
    ];

    /**
     * Get the project that owns the sandbox.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the task this sandbox was provisioned for.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
