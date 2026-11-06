<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherClaim extends Model
{
    use HasFactory;

    protected $fillable = ['voucher_id', 'claimed_at', 'used_at', 'order_id'];

    protected function casts(): array
    {
        return ['claimed_at' => 'datetime', 'used_at' => 'datetime'];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(ShopVoucher::class, 'voucher_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
