<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeartbeatLog extends Model
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
        'timestamp',
        'trigger',
        'run_type',
        'decisions',
        'tasks_queued',
        'provider_status',
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
            'decisions' => 'array',
            'tasks_queued' => 'array',
            'provider_status' => 'array',
            'timestamp' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
