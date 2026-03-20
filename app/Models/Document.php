<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'title',
        'content',
        'file_path',
        'file_name',
        'file_type',
        'project_id',
        'task_id',
        'access_level',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Task::class);
    }
}
