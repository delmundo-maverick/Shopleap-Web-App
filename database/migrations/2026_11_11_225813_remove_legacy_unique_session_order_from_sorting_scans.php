<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('sorting_scans', 'unique_session_order', 'unique')) {
            return;
        }

        Schema::table('sorting_scans', function (Blueprint $table): void {
            $table->dropUnique('unique_session_order');
        });
    }

    /**
     * Audit rows may repeat a session/order pair, so restoring uniqueness would
     * require deleting scan history. Keep the corrected schema on rollback.
     */
    public function down(): void {}
};
