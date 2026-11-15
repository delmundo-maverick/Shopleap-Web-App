<?php

namespace App\Services;

use App\Models\User;
use App\Support\LedgerPresentation;
use App\Support\MoneyWords;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;

class MoneyReceipts
{
    public const TYPES = ['cash-handovers', 'remittances', 'rider-payouts', 'seller-payouts', 'refunds'];

    /** @return array<string, mixed> */
    public function receipt(User $user, string $type, string $reference): array
    {
        $table = match ($type) {
            'cash-handovers' => 'rider_cash_handovers', 'remittances' => 'remittances', 'rider-payouts' => 'rider_payouts',
            'seller-payouts' => 'seller_payouts', 'refunds' => 'refund_payouts', default => abort(404),
        };
        $record = DB::table($table)->where('reference_no', $reference)->firstOrFail();
        $hub = isset($record->hub_id) ? DB::table('logistics_profiles')->where('id', $record->hub_id)->firstOrFail() : null;
        $rider = isset($record->rider_id) ? DB::table('courier_profiles')->where('id', $record->rider_id)->firstOrFail() : null;
        $order = $type === 'refunds' ? DB::table('orders')->where('id', $record->order_id)->firstOrFail() : null;
        $participants = [$hub->user_id ?? null, $rider->user_id ?? null, $record->seller_id ?? null, $record->buyer_id ?? null, $order->seller_id ?? null];
        abort_unless($user->role === 'super_admin' || in_array($user->id, array_filter($participants), true), 403);

        $riderName = $rider ? trim($rider->first_name.' '.$rider->last_name) : null;
        $sellerName = isset($record->seller_id) ? DB::table('users')->where('id', $record->seller_id)->value('name') : null;
        $buyerName = isset($record->buyer_id) ? DB::table('users')->where('id', $record->buyer_id)->value('name') : null;
        [$title, $from, $to, $dateColumn] = match ($type) {
            'cash-handovers' => ['Rider Cash Handover Receipt', $riderName, $hub->business_name, 'confirmed_at'],
            'remittances' => ['Remittance Receipt', $hub->business_name, 'Shopleap', 'submitted_at'],
            'rider-payouts' => ['Rider Payout Receipt', $hub->business_name, $riderName, 'paid_at'],
            'seller-payouts' => ['Seller Payout Receipt', 'Shopleap', $sellerName, 'requested_at'],
            'refunds' => ['Refund Receipt', 'Shopleap', $buyerName, 'requested_at'],
        };
        $amount = match ($type) {
            'cash-handovers' => LedgerService::decimal(LedgerService::cents((string) $record->amount_received) + LedgerService::cents((string) $record->settled_amount)),
            'remittances' => (string) $record->total_amount,
            default => (string) $record->amount,
        };
        $amount = LedgerService::decimal(LedgerService::cents($amount));
        $details = [];
        if ($type === 'cash-handovers') {
            $details = ['Expected cash' => LedgerPresentation::peso($record->amount_expected), 'Received at confirmation' => LedgerPresentation::peso($record->amount_received),
                'Additional cash received' => LedgerPresentation::peso($record->settled_amount),
                'Outstanding shortage' => LedgerPresentation::peso(LedgerService::decimal(LedgerService::cents((string) $record->short_amount) - LedgerService::cents((string) $record->settled_amount)))];
        } elseif ($type === 'seller-payouts') {
            $details = ['Payment method' => $record->method_type === 'bank' ? 'Bank' : $record->method_type,
                'Account name' => $record->account_name, 'Account ending' => '•••• '.$record->account_number_masked];
        } elseif ($type === 'refunds') {
            $details = ['Order' => $order->order_number];
        }
        foreach (['notes' => 'Notes', 'settlement_note' => 'Settlement note', 'rejection_reason' => 'Rejection reason'] as $field => $label) {
            if (! empty($record->$field)) {
                $details[$label] = $record->$field;
            }
        }
        $timeline = [];
        foreach (['requested_at' => 'Pending', 'submitted_at' => 'Submitted', 'confirmed_at' => (isset($record->short_amount) && LedgerService::cents((string) $record->short_amount) > 0 ? 'Short' : 'Confirmed'), 'processing_at' => 'Processing', 'verified_at' => 'Verified', 'settled_at' => 'Settled', 'paid_at' => 'Paid', 'rejected_at' => 'Rejected'] as $field => $label) {
            if (! empty($record->$field)) {
                $timeline[$label] = LedgerPresentation::dateTime($record->$field);
            }
        }
        $items = collect();
        if (in_array($type, ['cash-handovers', 'remittances', 'rider-payouts'], true)) {
            [$itemsTable, $foreignKey] = match ($type) {
                'cash-handovers' => ['rider_cash_handover_items', 'handover_id'], 'remittances' => ['remittance_items', 'remittance_id'],
                'rider-payouts' => ['rider_payout_items', 'rider_payout_id'],
            };
            $items = DB::table($itemsTable.' as i')->join('orders as o', 'o.id', '=', 'i.order_id')->where('i.'.$foreignKey, $record->id)
                ->orderBy('o.order_number')->select('o.order_number', 'i.amount')->get();
        }
        $qr = new Writer(new ImageRenderer(new RendererStyle(160, 24), new SvgImageBackEnd));

        return compact('title', 'from', 'to', 'amount', 'details', 'timeline', 'items') + [
            'reference' => $record->reference_no, 'date' => LedgerPresentation::dateTime($record->$dateColumn), 'status' => $record->status,
            'amountWords' => MoneyWords::pesos($amount), 'qrBase64' => base64_encode($qr->writeString($record->reference_no)),
            'logoBase64' => base64_encode(file_get_contents(public_path('images/Shopleap-Logo.png'))),
        ];
    }
}
