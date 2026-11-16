<?php

namespace Database\Seeders;

use App\Models\PlatformPolicy;
use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        PlatformSetting::set('platform_name', 'Shopleap');
        PlatformSetting::set('support_email', 'support@shopleap.shop');
        PlatformSetting::set('commission_rate', '10');

        foreach (PlatformPoliciesSeeder::definitions() as $slug => $definition) {
            PlatformPolicy::firstOrCreate(['slug' => $slug], $definition);
        }
    }
}
