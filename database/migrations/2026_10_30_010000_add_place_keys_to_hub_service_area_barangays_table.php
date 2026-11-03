<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A barangay can belong to only one hub. The normalized keys make that rule
     * enforceable in Postgres even when the stored names differ by case or spacing.
     */
    public function up(): void
    {
        Schema::table('hub_service_area_barangays', function (Blueprint $table) {
            $table->string('province_key')->nullable();
            $table->string('municipality_key')->nullable();
            $table->string('barangay_key')->nullable();
        });

        DB::statement(<<<'SQL'
            UPDATE hub_service_area_barangays AS b
            SET province_key = lower(btrim(regexp_replace(a.province, '\s+', ' ', 'g'))),
                municipality_key = lower(btrim(regexp_replace(a.municipality, '\s+', ' ', 'g'))),
                barangay_key = lower(btrim(regexp_replace(b.barangay, '\s+', ' ', 'g')))
            FROM hub_service_areas AS a
            WHERE a.id = b.hub_service_area_id
        SQL);

        $missing = DB::table('hub_service_area_barangays')
            ->whereNull('province_key')
            ->orWhereNull('municipality_key')
            ->orWhereNull('barangay_key')
            ->pluck('id');

        if ($missing->isNotEmpty()) {
            throw new RuntimeException(
                'These hub_service_area_barangays rows have no service area and cannot be keyed: '
                .$missing->implode(', ')
            );
        }

        $conflicts = DB::table('hub_service_area_barangays as b')
            ->join('hub_service_areas as a', 'a.id', '=', 'b.hub_service_area_id')
            ->selectRaw('b.province_key, b.municipality_key, b.barangay_key, count(*) as rows, string_agg(distinct a.logistics_profile_id::text, \',\') as hub_ids')
            ->groupBy('b.province_key', 'b.municipality_key', 'b.barangay_key')
            ->havingRaw('count(*) > 1')
            ->get();

        if ($conflicts->isNotEmpty()) {
            $details = $conflicts
                ->map(fn ($row) => "{$row->barangay_key}, {$row->municipality_key}, {$row->province_key} (hubs: {$row->hub_ids})")
                ->implode('; ');

            throw new RuntimeException(
                'Cannot make each barangay belong to one hub because these places are already shared: '
                .$details
            );
        }

        DB::statement('ALTER TABLE hub_service_area_barangays ALTER COLUMN province_key SET NOT NULL');
        DB::statement('ALTER TABLE hub_service_area_barangays ALTER COLUMN municipality_key SET NOT NULL');
        DB::statement('ALTER TABLE hub_service_area_barangays ALTER COLUMN barangay_key SET NOT NULL');

        Schema::table('hub_service_area_barangays', function (Blueprint $table) {
            $table->unique(
                ['province_key', 'municipality_key', 'barangay_key'],
                'hub_service_area_barangays_place_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('hub_service_area_barangays', function (Blueprint $table) {
            $table->dropUnique('hub_service_area_barangays_place_unique');
            $table->dropColumn(['province_key', 'municipality_key', 'barangay_key']);
        });
    }
};
