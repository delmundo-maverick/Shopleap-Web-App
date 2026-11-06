<?php

namespace Database\Factories;

use App\Models\ShopVoucher;
use App\Models\User;
use App\Models\VoucherClaim;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VoucherClaim> */
class VoucherClaimFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'voucher_id' => ShopVoucher::factory(), 'claimed_at' => now()];
    }
}
