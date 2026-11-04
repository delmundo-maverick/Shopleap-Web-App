<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('buyer_profiles')->orderBy('id')->chunkById(200, function ($profiles): void {
            foreach ($profiles as $profile) {
                if (! $profile->province || ! $profile->municipality || DB::table('buyer_addresses')->where('user_id', $profile->user_id)->exists()) {
                    continue;
                }
                $province = preg_replace('/\s+/', ' ', trim($profile->province));
                $canonical = DB::table('hub_service_areas')->whereRaw('LOWER(TRIM(province)) = ?', [mb_strtolower($province)])->value('province');
                DB::table('buyer_addresses')->insert([
                    'user_id' => $profile->user_id, 'label' => 'Home',
                    'recipient_name' => DB::table('users')->where('id', $profile->user_id)->value('name'),
                    'phone' => $profile->contact_no ?? '', 'province' => $canonical ?? $province,
                    'city_municipality' => trim($profile->municipality), 'barangay' => trim($profile->barangay ?? ''),
                    'street_address' => trim(($profile->house_number ?? '').' '.($profile->street ?? '')),
                    'notes' => $profile->additional_address, 'is_default' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Preserve buyer address data on rollback; legacy profile columns are unchanged.
    }
};
