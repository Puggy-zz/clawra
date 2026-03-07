<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Provider extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'api_protocol',
        'usage_snapshot',
        'rate_limits',
        'capability_tags',
        'priority_preferences',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usage_snapshot' => 'array',
            'rate_limits' => 'array',
            'capability_tags' => 'array',
            'priority_preferences' => 'array',
        ];
    }
}
