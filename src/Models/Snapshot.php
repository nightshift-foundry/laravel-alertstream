<?php

namespace NightshiftFoundry\AlertStream\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class Snapshot extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
        'line' => 'integer',
        'occurrences' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    // ...existing code...

    /**
     * Define the prunable query.
     *
     * Works automatically with Laravel's `php artisan model:prune` scheduler.
     */
    public function prunable(): Builder
    {
        return static::expired();
    }

    public function getTable(): string
    {
        return config('alertstream.snapshots.table', 'alertstream_snapshots');
    }

    /**
     * Scope to snapshots older than the configured retention period.
     */
    public function scopeExpired(Builder $query, ?int $days = null): Builder
    {
        $days = $days ?? config('alertstream.snapshots.retention_days', 30);

        return $query->where('created_at', '<', now()->subDays($days));
    }

    /**
     * Get the public URL for this snapshot.
     */
    public function getUrlAttribute(): string
    {
        return route('alertstream.snapshots.show', $this->hash);
    }
}
