<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentBagItem extends Model
{
    protected $fillable = [
        'bag_id',
        'order_id',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }

    public function bag()
    {
        return $this->belongsTo(ShipmentBag::class, 'bag_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
