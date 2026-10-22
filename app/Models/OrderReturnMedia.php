<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderReturnMedia extends Model
{
    /** Same private disk as review media. */
    const DISK = ReviewMedia::DISK;

    protected $table = 'order_return_media';

    protected $fillable = ['order_return_id', 'path', 'type', 'sort_order'];

    public function orderReturn()
    {
        return $this->belongsTo(OrderReturn::class);
    }

    /** Evidence is streamed to the buyer, seller and admin only. */
    public function getUrlAttribute(): string
    {
        return route('returns.media', $this);
    }
}
