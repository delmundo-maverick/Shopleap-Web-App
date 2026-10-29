<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SortingScan extends Model
{
    const RESULT_SORTED = 'sorted';

    const RESULT_DUPLICATE = 'duplicate';

    const RESULT_REJECTED = 'rejected';

    const RESULT_NEEDS_REVIEW = 'needs_review';

    protected $fillable = [
        'sorting_session_id',
        'order_id',
        'scanned_code',
        'result',
        'message',
        'destination_municipality',
        'destination_province',
        'destination_barangay',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function session()
    {
        return $this->belongsTo(SortingSession::class, 'sorting_session_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
