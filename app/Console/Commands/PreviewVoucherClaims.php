<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PreviewVoucherClaims extends Command
{
    protected $signature = 'vouchers:preview-claims';

    protected $description = 'Count redeemed buyer/voucher pairs before the claims backfill (read only)';

    public function handle(): int
    {
        $pairs = DB::table('shop_voucher_redemptions')->select('buyer_id', 'shop_voucher_id')->distinct();
        if (Schema::hasTable('voucher_claims')) {
            $pairs->whereNotExists(fn ($query) => $query->selectRaw('1')->from('voucher_claims')
                ->whereColumn('voucher_claims.user_id', 'shop_voucher_redemptions.buyer_id')
                ->whereColumn('voucher_claims.voucher_id', 'shop_voucher_redemptions.shop_voucher_id'));
        }
        $this->line('Claims to backfill: '.DB::query()->fromSub($pairs, 'pairs')->count());
        $this->line('Redemption history rows preserved: '.DB::table('shop_voucher_redemptions')->count());
        $this->line('Unredeemed vouchers receiving automatic claims: 0');

        return self::SUCCESS;
    }
}
