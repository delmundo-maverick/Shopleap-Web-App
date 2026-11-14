<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \LogicException('Provision production administrators with unique credentials outside this development seeder.');
        }

        User::updateOrCreate(
            ['email' => 'superadmin@shopleap.com', 'role' => 'super_admin'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('superadmindash123'),
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]
        );
    }
}
