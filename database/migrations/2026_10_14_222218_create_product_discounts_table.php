<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_discounts', function (Blueprint $table) {
            $table->id();

            // unique(): one discount per product. Sellers edit it rather than stacking rows.
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();

            $table->string('type', 10);          // 'percent' | 'fixed'
            $table->decimal('value', 10, 2);     // 20 => 20% off, or ₱20 off
            $table->timestamp('starts_at')->nullable();   // null = starts immediately
            $table->timestamp('ends_at')->nullable();     // null = no end date
            $table->boolean('is_active')->default(true);  // seller can pause without deleting

            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        // Move existing products.discount_price values into the new table so nothing
        // currently on sale disappears. products.discount_price is no longer read or
        // written by the app after this; drop the column in a later release.
        DB::table('products')
            ->whereNotNull('discount_price')
            ->whereColumn('discount_price', '<', 'price')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                $now = now();

                foreach ($rows as $p) {
                    DB::table('product_discounts')->insert([
                        'product_id' => $p->id,
                        'seller_id' => $p->seller_id,
                        'type' => 'fixed',
                        'value' => round((float) $p->price - (float) $p->discount_price, 2),
                        'starts_at' => null,
                        'ends_at' => null,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('products')->whereIn('id', $rows->pluck('id'))->update(['discount_price' => null]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_discounts');
    }
};
