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

        $defaults = [
            'terms-of-service' => 'Terms of Service',
            'privacy-policy' => 'Privacy Policy',
        ];

        foreach ($defaults as $slug => $title) {
            PlatformPolicy::firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'body' => "This is a placeholder for Shopleap's {$title}. An administrator should replace this text under Global Platform Settings.",
            ]);
        }
    }
}
