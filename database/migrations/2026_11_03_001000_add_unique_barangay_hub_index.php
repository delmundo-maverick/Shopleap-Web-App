<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure each barangay is owned by exactly one hub. The earlier place-key
     * migration already adds an equivalent index, so this one is idempotent and
     * only adds the guard when it is missing.
     */
    public function up(): void
    {
        if ($this->hasOwnershipIndex()) {
            return;
        }

        $conflicts = DB::table('hub_service_area_barangays as b')
            ->join('hub_service_areas as a', 'a.id', '=', 'b.hub_service_area_id')
            ->select([
                'b.province_key',
                'b.municipality_key',
                'b.barangay_key',
                'a.logistics_profile_id',
            ])
            ->get()
            ->groupBy(fn ($row) => implode('|', [
                $row->province_key,
                $row->municipality_key,
                $row->barangay_key,
            ]))
            ->filter(fn ($rows) => $rows->count() > 1);

        if ($conflicts->isNotEmpty()) {
            $details = $conflicts->map(function ($rows) {
                $row = $rows->first();
                $hubs = $rows->pluck('logistics_profile_id')->unique()->sort()->implode(', ');

                return "{$row->barangay_key}, {$row->municipality_key}, {$row->province_key} (hubs: {$hubs})";
            })->implode('; ');

            throw new RuntimeException(
                'Cannot make each barangay belong to one hub because these places are already shared: '.$details
            );
        }

        Schema::table('hub_service_area_barangays', function (Blueprint $table) {
            $table->unique(['province_key', 'municipality_key', 'barangay_key'], 'uq_barangay_hub');
        });
    }

    public function down(): void
    {
        foreach (Schema::getIndexes('hub_service_area_barangays') as $index) {
            if ($index['name'] === 'uq_barangay_hub') {
                Schema::table('hub_service_area_barangays', function (Blueprint $table) {
                    $table->dropUnique('uq_barangay_hub');
                });

                return;
            }
        }
    }

    /** The unique index may already exist under the name from the place-key migration. */
    private function hasOwnershipIndex(): bool
    {
        foreach (Schema::getIndexes('hub_service_area_barangays') as $index) {
            if (! $index['unique']) {
                continue;
            }

            $columns = $index['columns'];
            sort($columns);

            if ($columns === ['barangay_key', 'municipality_key', 'province_key']) {
                return true;
            }
        }

        return false;
    }
};
