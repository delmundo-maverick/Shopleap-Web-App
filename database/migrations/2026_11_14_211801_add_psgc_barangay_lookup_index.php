<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $concurrently = DB::connection()->getDriverName() === 'pgsql' ? ' CONCURRENTLY' : '';
        DB::statement('CREATE INDEX'.$concurrently.' psgc_barangays_case_insensitive_place_idx ON psgc_barangays (LOWER(province), LOWER(municipality), barangay)');
    }

    public function down(): void
    {
        $concurrently = DB::connection()->getDriverName() === 'pgsql' ? ' CONCURRENTLY' : '';
        DB::statement('DROP INDEX'.$concurrently.' psgc_barangays_case_insensitive_place_idx');
    }
};
