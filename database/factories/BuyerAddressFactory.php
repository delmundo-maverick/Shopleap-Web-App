<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BuyerAddressFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'recipient_name' => fake()->name(), 'phone' => '09123456789',
            'province' => 'Cebu', 'city_municipality' => 'Cebu City', 'barangay' => 'Lahug',
            'street_address' => '10 Main Street', 'postal_code' => '6000', 'is_default' => true];
    }
}
