<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopVoucherRedemption extends Model
{
    protected $fillable = [
        'shop_voucher_id',
        'buyer_id',
        'seller_id',
        'order_id',
        'discount_amount',
        'order_subtotal',
        'status',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
            'order_subtotal' => 'decimal:2',
            'used_at' => 'datetime',
        ];
    }

    public function voucher()
    {
        return $this->belongsTo(ShopVoucher::class, 'shop_voucher_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
