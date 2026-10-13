<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'order_item_id',
        'order_id',
        'product_id',
        'buyer_id',
        'seller_id',
        'rating',
        'comment',
        'seller_reply',
        'seller_replied_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'seller_replied_at' => 'datetime',
        ];
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function media()
    {
        return $this->hasMany(ReviewMedia::class)->orderBy('sort_order');
    }

    /** "Juan Dela Cruz" -> "J*****z" so buyers keep some privacy on shown feedback. */
    public function getBuyerMaskedNameAttribute(): string
    {
        $name = trim((string) $this->buyer?->name);

        if ($name === '') {
            return 'Anonymous buyer';
        }

        $length = mb_strlen($name);

        if ($length <= 2) {
            return mb_substr($name, 0, 1) . '*';
        }

        return mb_substr($name, 0, 1)
            . str_repeat('*', min($length - 2, 5))
            . mb_substr($name, -1);
    }
}
