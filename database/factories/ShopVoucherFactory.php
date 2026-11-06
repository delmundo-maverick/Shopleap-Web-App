<?php

namespace Database\Factories;

use App\Models\ShopVoucher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ShopVoucher> */
class ShopVoucherFactory extends Factory
{
    public function definition(): array
    {
        return ['seller_id' => User::factory(), 'code' => strtoupper(fake()->unique()->bothify('VOUCHER????####')),
            'discount_type' => 'fixed', 'discount_value' => 20, 'min_purchase_amount' => 0,
            'used_quantity' => 0, 'per_buyer_limit' => 1, 'status' => 'active', 'is_active' => true];
    }
}
