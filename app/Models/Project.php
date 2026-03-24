<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'goals',
        'status',
        'state_document',
        'current_intent',
        'workspace_path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state_document' => 'array',
        ];
    }

    /**
     * Get the tasks for the project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the sandboxes for the project.
     */
    public function sandboxes(): HasMany
    {
        return $this->hasMany(Sandbox::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ProjectConversation::class);
    }

    /**
     * Get the path to this project's app-managed data directory (index, plans, documents).
     * e.g. {storage}/app/project-data/{id}/
     */
    public function projectDataPath(string $path = ''): string
    {
        $base = storage_path('app/project-data/'.$this->id);

        return $path !== '' ? $base.'/'.ltrim($path, '/') : $base;
    }
}
