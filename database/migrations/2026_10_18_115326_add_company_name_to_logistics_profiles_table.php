<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_profiles', function (Blueprint $table) {
            $table->string('company_name', 150)->nullable()->after('business_name');
        });

        // Existing hubs become their own company until edited.
        DB::table('logistics_profiles')->whereNull('company_name')->update(['company_name' => DB::raw('business_name')]);
    }

    public function down(): void
    {
        Schema::table('logistics_profiles', function (Blueprint $table) {
            $table->dropColumn('company_name');
        });
    }
};
