<?php

namespace App\Policies;

use App\Models\ShopVoucher;
use App\Models\User;

class ShopVoucherPolicy
{
    public function view(User $user, ShopVoucher $shopVoucher): bool
    {
        return $user->id === $shopVoucher->seller_id;
    }

    public function update(User $user, ShopVoucher $shopVoucher): bool
    {
        return $user->id === $shopVoucher->seller_id;
    }

    public function delete(User $user, ShopVoucher $shopVoucher): bool
    {
        return $user->id === $shopVoucher->seller_id;
    }
}
