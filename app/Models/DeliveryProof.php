<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryProof extends Model
{
    protected $guarded = [];

    protected $hidden = ['image_path', 'thumbnail_path', 'latitude', 'longitude', 'notes'];

    protected function casts(): array
    {
        return ['captured_at' => 'immutable_datetime', 'uploaded_at' => 'immutable_datetime', 'expired_at' => 'immutable_datetime'];
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(CourierProfile::class);
    }

    /** @return array{uploaded: bool, captured_at: ?string} */
    public function riderSummary(): array
    {
        return ['uploaded' => true, 'captured_at' => $this->captured_at?->toIso8601String()];
    }
}
