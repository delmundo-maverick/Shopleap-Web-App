<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SortingSession extends Model
{
    protected $fillable = [
        'logistics_profile_id',
        'user_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'auto_closed',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'auto_closed' => 'boolean',
            'duration_seconds' => 'integer'
        ];
    }

    public function logisticsProfile()
    {
        return $this->belongsTo(LogisticsProfile::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scans()
    {
        return $this->hasMany(SortingScan::class);
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    public function elapsedSeconds(?Carbon $now = null): int
    {
        $now = $now ?? now();

        if ($this->ended_at) {
            return $this->durationSeconds();
        }

        return max(0, (int) floor($this->started_at->diffInSeconds($now)));
    }

    public function durationSeconds(): int
    {
        if ($this->duration_seconds !== null) {
            return (int) $this->duration_seconds;
        }

        if ($this->ended_at) {
            return max(0, (int) floor($this->started_at->diffInSeconds($this->ended_at)));
        }

        return 0;
    }

    public function sortedCount(): int
    {
        return $this->scans()->where('result', 'sorted')->count();
    }

    public function duplicateCount(): int
    {
        return $this->scans()->where('result', 'duplicate')->count();
    }

    public function rejectedCount(): int
    {
        return $this->scans()->where('result', 'rejected')->count();
    }

    public function needsReviewCount(): int
    {
        return $this->scans()->where('result', 'needs_review')->count();
    }

    public function assignedDuringSessionCount(): int
    {
        if (! $this->started_at) {
            return 0;
        }

        $query = Order::where('logistics_profile_id', $this->logistics_profile_id)
            ->where('status', Order::STATUS_ASSIGNED_TO_RIDER)
            ->where('updated_at', '>=', $this->started_at);

        if ($this->ended_at) {
            $query->where('updated_at', '<=', $this->ended_at);
        }

        return $query->count();
    }

    public function scopeActive($query)
    {
        return $query->whereNull('ended_at');
    }

    public function scopeForHub($query, int $logisticsProfileId)
    {
        return $query->where('logistics_profile_id', $logisticsProfileId);
    }
}
