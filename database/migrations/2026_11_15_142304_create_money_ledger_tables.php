<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('owner_type', 20);
            $table->unsignedBigInteger('owner_id');
            $table->string('account_type', 40);
            $table->char('currency', 3)->default('PHP');
            $table->unique(['owner_type', 'owner_id', 'account_type']);
        });
        Schema::create('ledger_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_no', 50)->unique();
            $table->string('type', 40);
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('description');
            $table->string('status', 20)->default('posted');
            $table->string('idempotency_key')->unique();
            $table->char('payload_hash', 64);
            $table->timestamp('posted_at')->index();
            $table->index(['posted_at', 'id']);
            $table->foreignId('reversal_of')->nullable()->unique()->constrained('ledger_transactions')->restrictOnDelete();
            $table->index(['order_id', 'type']);
        });
        Schema::create('ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transaction_id')->constrained('ledger_transactions')->restrictOnDelete();
            $table->foreignId('account_id')->constrained('wallet_accounts')->restrictOnDelete();
            $table->enum('direction', ['debit', 'credit']);
            $table->decimal('amount', 12, 2);
            $table->index(['account_id', 'transaction_id']);
            $table->index('transaction_id');
        });
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::unprepared("CREATE FUNCTION reject_ledger_mutation() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'Ledger records are immutable; post a reversal'; END; $$");
        }
        foreach (['ledger_entries', 'ledger_transactions'] as $table) {
            foreach (['UPDATE', 'DELETE'] as $operation) {
                $trigger = $table.'_'.strtolower($operation).'_immutable';
                if ($driver === 'pgsql') {
                    DB::unprepared("CREATE TRIGGER {$trigger} BEFORE {$operation} ON {$table} FOR EACH ROW EXECUTE FUNCTION reject_ledger_mutation()");
                } elseif ($driver === 'sqlite') {
                    DB::unprepared("CREATE TRIGGER {$trigger} BEFORE {$operation} ON {$table} BEGIN SELECT RAISE(ABORT, 'Ledger records are immutable; post a reversal'); END");
                } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
                    DB::unprepared("CREATE TRIGGER {$trigger} BEFORE {$operation} ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ledger records are immutable; post a reversal'");
                } else {
                    throw new RuntimeException('Ledger immutability is not configured for this database driver.');
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_transactions');
        Schema::dropIfExists('wallet_accounts');
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS reject_ledger_mutation()');
        }
    }
};
