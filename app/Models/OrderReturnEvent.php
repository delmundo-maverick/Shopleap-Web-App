<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderReturnEvent extends Model
{
    protected $fillable = ['order_return_id', 'actor_id', 'title', 'note'];

    public function orderReturn()
    {
        return $this->belongsTo(OrderReturn::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
