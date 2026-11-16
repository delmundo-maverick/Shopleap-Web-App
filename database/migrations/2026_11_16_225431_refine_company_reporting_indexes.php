<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_profiles', function (Blueprint $table): void {
            $table->index(['company_id', 'id'], 'company_hubs_filter_index');
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('company_reports_delivery_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->index(['earning_hub_id', 'delivered_at'], 'company_reports_delivery_index');
        });
        Schema::table('logistics_profiles', function (Blueprint $table): void {
            $table->dropIndex('company_hubs_filter_index');
        });
    }
};
