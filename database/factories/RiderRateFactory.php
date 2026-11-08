<?php

namespace Database\Factories;

use App\Models\LogisticsProfile;
use App\Models\RiderRate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiderRate>
 */
class RiderRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 0, 39),
            'effective_from' => now(),
            'created_by' => User::factory(),
        ];
    }

    public function forHub(LogisticsProfile $hub): static
    {
        return $this->state(fn (array $attributes): array => ['logistics_profile_id' => $hub->id, 'created_by' => $hub->user_id]);
    }
}
