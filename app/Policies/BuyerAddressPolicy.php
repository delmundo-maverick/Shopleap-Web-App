<?php

namespace App\Policies;

use App\Models\BuyerAddress;
use App\Models\User;

class BuyerAddressPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'buyer';
    }

    public function create(User $user): bool
    {
        return $user->role === 'buyer';
    }

    public function view(User $user, BuyerAddress $address): bool
    {
        return $user->role === 'buyer' && $user->id === $address->user_id;
    }

    public function update(User $user, BuyerAddress $address): bool
    {
        return $this->view($user, $address);
    }

    public function delete(User $user, BuyerAddress $address): bool
    {
        return $this->view($user, $address);
    }
}
