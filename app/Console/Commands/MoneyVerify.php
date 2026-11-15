<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\Courier\DeliveryController;
use App\Http\Controllers\Buyer\OrderController;
use App\Models\CourierProfile;
use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\RiderRate;
use App\Models\ShopVoucher;
use App\Models\User;
use App\Services\LedgerService;
use App\Services\MoneyMovements;
use App\Services\OrderCancellation;
use App\Services\OrderLedger;
use App\Services\OrderReturns;
use App\Services\OrderTransitions;
use App\Services\ParcelLifecycle;
use App\Services\Payouts;
use App\Services\RiderEarnings;
use App\Support\OrderCodes;
use Closure;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

#[Signature('money:verify')]
#[Description('Verify the money flow using disposable, rolled-back scenarios')]
class MoneyVerify extends Command
{
    private User $buyer;

    private User $seller;

    private User $admin;

    private User $hubUser;

    private User $riderUser;

    private LogisticsProfile $hub;

    private CourierProfile $rider;

    private Product $product;

    private ShopVoucher $voucher;

    private int $methodId;

    private int $firstTransactionId;

    private int $failures = 0;

    public function handle(): int
    {
        if ($this->laravel->isProduction()) {
            $this->error('FAIL Production guard: expected non-production; actual production.');

            return self::FAILURE;
        }
        $level = DB::transactionLevel();
        $temporaryRoot = sys_get_temp_dir().'/money-verify-'.Str::uuid();
        $originalDisk = Storage::disk('r2');
        Storage::set('r2', Storage::build(['driver' => 'local', 'root' => $temporaryRoot, 'throw' => true]));
        DB::beginTransaction();
        try {
            $this->fixtures();
            foreach ($this->scenarios() as $name => $scenario) {
                $scenarioLevel = DB::transactionLevel();
                DB::beginTransaction();
                $this->firstTransactionId = (int) DB::table('ledger_transactions')->max('id');
                try {
                    $scenario();
                    $this->line('PASS '.$name);
                } catch (Throwable $exception) {
                    $this->failures++;
                    $message = $exception instanceof RuntimeException && str_contains($exception->getMessage(), 'expected ')
                        ? $exception->getMessage() : 'expected successful scenario; actual '.$exception::class.' '.$exception->getMessage();
                    $this->line('FAIL '.$name.': '.preg_replace('/\s+/', ' ', $message));
                } finally {
                    DB::rollBack($scenarioLevel);
                }
            }
        } catch (Throwable $exception) {
            $this->failures++;
            $this->line('FAIL Setup: expected usable fixtures; actual '.preg_replace('/\s+/', ' ', $exception->getMessage()));
        } finally {
            DB::rollBack($level);
            Storage::set('r2', $originalDisk);
            File::deleteDirectory($temporaryRoot);
        }

        return $this->failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function fixtures(): void
    {
        $this->buyer = User::factory()->create(['role' => 'buyer', 'account_status' => 'active']);
        $this->seller = User::factory()->create(['role' => 'seller', 'account_status' => 'active']);
        $this->admin = User::factory()->create(['role' => 'super_admin', 'account_status' => 'active']);
        $this->hubUser = User::factory()->create(['role' => 'logistics', 'account_status' => 'active']);
        $this->riderUser = User::factory()->create(['role' => 'courier', 'account_status' => 'active']);
        $person = ['first_name' => 'Money', 'last_name' => 'Verify', 'sex' => 'male', 'contact_no' => '09123456789',
            'birthday' => '1990-01-01', 'age' => 36, 'province' => 'Cebu', 'municipality' => 'Cebu City',
            'barangay' => 'Lahug', 'street_address' => 'Verification only', 'status' => 'approved'];
        $this->hub = LogisticsProfile::create($person + ['user_id' => $this->hubUser->id, 'business_name' => 'Money Verify',
            'id_upload_path' => 'verification-only', 'business_permit_path' => 'verification-only']);
        $this->rider = CourierProfile::create($person + ['user_id' => $this->riderUser->id, 'logistics_profile_id' => $this->hub->id,
            'vehicle_type' => 'motorcycle', 'plate_number' => 'VERIFY', 'or_cr_upload_path' => 'verification-only',
            'license_upload_path' => 'verification-only', 'is_active' => true]);
        RiderRate::factory()->create(['logistics_profile_id' => $this->hub->id, 'created_by' => $this->hubUser->id,
            'amount' => '20.00', 'effective_from' => now()->subDay()]);
        $this->product = Product::factory()->create(['seller_id' => $this->seller->id, 'price' => '1000.00']);
        $this->voucher = ShopVoucher::factory()->create(['seller_id' => $this->seller->id, 'discount_value' => '100.00']);
        app(Payouts::class)->addMethod($this->seller->id, ['type' => 'GCash', 'account_name' => 'Money Verify', 'account_number_masked' => '1234']);
        $this->methodId = (int) DB::table('seller_payout_methods')->where('seller_id', $this->seller->id)->value('id');
        DB::table('platform_settings')->updateOrInsert(['key' => 'minimum_payout'], ['value' => '100.00']);
    }

    /** @return array<string, Closure(): void> */
    private function scenarios(): array
    {
        return [
            '1. Delivered balances' => function (): void {
                $this->delivered();
                $this->balance('rider', $this->rider->id, 'cash_on_hand', 93000);
                $this->balance('hub', $this->hub->id, 'cod_to_remit', 90000);
                $this->balance('hub', $this->hub->id, 'shipping_earned', 3000);
                $this->balance('rider', $this->rider->id, 'earnings_accrued', 2000);
                $this->balance('seller', $this->seller->id, 'pending', 81000);
                $this->balance('platform', 0, 'commission_pending', 9000);
            },
            '2. Delivery and rider API idempotency' => function (): void {
                $order = $this->delivered();
                $before = $this->ledgerSnapshot();
                $this->equal(true, app(OrderTransitions::class)->delivered($order, $this->riderUser->id), 'delivery retry');
                app(OrderLedger::class)->delivered($order);
                $request = $this->request($this->riderUser);
                $request->attributes->set('courier', $this->rider);
                foreach (range(1, 2) as $attempt) {
                    $response = app(DeliveryController::class)->delivered($request, $order->fresh(), app(OrderTransitions::class), app(RiderEarnings::class));
                    $this->equal(200, $response->getStatusCode(), 'rider API retry '.$attempt);
                }
                $this->equal($before, $this->ledgerSnapshot(), 'duplicate delivery ledger');
            },
            '3. Buyer confirms receipt' => function (): void {
                $this->confirmReceipt($this->delivered());
                $this->balance('seller', $this->seller->id, 'pending', 0);
                $this->balance('seller', $this->seller->id, 'available', 81000);
                $this->balance('platform', 0, 'commission_pending', 0);
                $this->balance('platform', 0, 'commission_earned', 9000);
            },
            '4. Full handover, shortage and settlement' => function (): void {
                $this->delivered();
                $this->handover('930.00');
                $this->balance('rider', $this->rider->id, 'cash_on_hand', 0);
                $this->balance('hub', $this->hub->id, 'cash_held', 93000);
                $this->delivered();
                $short = $this->handover('900.00');
                $this->equal('short', $short->status, 'handover status');
                $this->equal('30.00', $short->short_amount, 'shortage');
                $this->balance('rider', $this->rider->id, 'cash_on_hand', 3000);
                $settled = app(MoneyMovements::class)->settleShortage($this->hub->id, $short->reference_no, 'Remaining cash received.', $this->hubUser->id);
                $this->equal('settled', $settled->status, 'settlement status');
                $this->equal('30.00', $settled->settled_amount, 'shortage settled');
                $this->balance('rider', $this->rider->id, 'cash_on_hand', 0);
                $this->balance('hub', $this->hub->id, 'cash_held', 186000);
            },
            '5. Remittance submission, rejection, resubmission and verification' => function (): void {
                $order = $this->delivered();
                $this->handover('930.00');
                $before = $this->ledgerSnapshot();
                $remittance = $this->remit($order);
                $this->equal('900.00', $remittance->total_amount, 'remittance amount');
                $this->equal($before, $this->ledgerSnapshot(), 'submitted remittance ledger');
                $this->blocked(fn () => $this->remit($order), ValidationException::class, 'duplicate active remittance');
                app(MoneyMovements::class)->decideRemittance($remittance->reference_no, false, 'Proof rejected.', $this->admin->id);
                $this->equal($before, $this->ledgerSnapshot(), 'rejected remittance ledger');
                $remittance = $this->remit($order);
                app(MoneyMovements::class)->decideRemittance($remittance->reference_no, true, null, $this->admin->id);
                $this->balance('hub', $this->hub->id, 'cod_to_remit', 0);
                $this->balance('platform', 0, 'cod_held', 90000);
                $this->balance('hub', $this->hub->id, 'cash_held', 3000);
                $this->blocked(fn () => $this->remit($order), ValidationException::class, 'already remitted order');
            },
            '6. Rider payout and duplicate protection' => function (): void {
                $order = $this->delivered();
                $this->handover('930.00');
                $pay = fn () => app(Payouts::class)->payRider($this->hub->id, $this->rider->id, [$order->order_number], null, (string) Str::uuid(), $this->hubUser->id);
                $pay();
                $this->balance('rider', $this->rider->id, 'earnings_accrued', 0);
                $this->balance('rider', $this->rider->id, 'earnings_paid', 2000);
                $this->balance('hub', $this->hub->id, 'rider_pay_owed', 0);
                $this->blocked($pay, ValidationException::class, 'duplicate rider payout');
            },
            '7. Seller minimum, locking, rejection, payment and duplicate protection' => function (): void {
                $order = $this->delivered();
                $this->confirmReceipt($order);
                $this->collectAndRemit($order);
                $this->blocked(fn () => $this->sellerRequest('99.99'), ValidationException::class, 'below minimum');
                $payout = $this->sellerRequest('810.00');
                $this->balance('seller', $this->seller->id, 'available', 0);
                $this->balance('seller', $this->seller->id, 'payout_locked', 81000);
                $this->blocked(fn () => $this->sellerRequest('100.00'), ValidationException::class, 'second pending request');
                app(Payouts::class)->decideSeller($payout->reference_no, 'reject', 'Verification rejection.', $this->admin->id);
                $this->balance('seller', $this->seller->id, 'available', 81000);
                $this->balance('seller', $this->seller->id, 'payout_locked', 0);
                $this->withdraw('810.00');
                $this->balance('seller', $this->seller->id, 'payout_locked', 0);
                $this->balance('seller', $this->seller->id, 'withdrawn', 81000);
            },
            '8. Return before confirmation preserves shipping and rider pay' => function (): void {
                $return = $this->refund($this->delivered());
                $this->balance('seller', $this->seller->id, 'pending', 0);
                $this->balance('platform', 0, 'commission_pending', 0);
                $this->balance('buyer', $this->buyer->id, 'refunds_issued', 90000);
                $this->equal('900.00', $return->refund_amount, 'refund payable');
                $this->balance('hub', $this->hub->id, 'shipping_earned', 3000);
                $this->balance('rider', $this->rider->id, 'earnings_accrued', 2000);
                $this->balance('hub', $this->hub->id, 'rider_pay_owed', 2000);
            },
            '9. Return after withdrawal and recovery from next payout' => function (): void {
                $order = $this->delivered();
                $this->confirmReceipt($order);
                $this->collectAndRemit($order);
                $this->withdraw('810.00');
                $this->refund($order->fresh());
                $this->balance('seller', $this->seller->id, 'available', -81000);
                $this->balance('platform', 0, 'commission_earned', 0);
                $this->confirmReceipt($this->delivered());
                $this->balance('seller', $this->seller->id, 'available', 0);
                $this->blocked(fn () => $this->sellerRequest('810.00'), ValidationException::class, 'shortfall recovered before payout');
                $next = $this->delivered();
                $this->confirmReceipt($next);
                $this->collectAndRemit($next);
                $this->withdraw('810.00');
                $this->balance('seller', $this->seller->id, 'available', 0);
                $this->balance('seller', $this->seller->id, 'withdrawn', 162000);
            },
            '10. Failed delivery and cancellation post nothing' => function (): void {
                $before = $this->ledgerSnapshot();
                $order = $this->order();
                $this->equal(1, app(OrderTransitions::class)->failed($order, 'Buyer unavailable', null, $this->riderUser->id), 'failed delivery attempt');
                $order = $this->order(Order::STATUS_PLACED);
                $this->equal(true, app(OrderCancellation::class)->cancel($order, 'buyer', 'Changed my mind', null, $this->buyer->id, [Order::STATUS_PLACED]), 'cancellation');
                $this->equal($before, $this->ledgerSnapshot(), 'failed/cancelled ledger');
            },
            '11. Refund payment reduces platform COD held' => function (): void {
                $order = $this->delivered();
                $this->collectAndRemit($order);
                $return = $this->refund($order);
                $this->balance('platform', 0, 'cod_held', 90000);
                $this->equal(true, app(OrderReturns::class)->markRefunded($return, $this->admin->id), 'mark refund paid');
                $this->balance('platform', 0, 'cod_held', 0);
                $this->equal(OrderReturn::STATUS_REFUNDED, $return->fresh()->status, 'refund status');
                $this->equal('900.00', DB::table('refund_payouts')->where('order_return_id', $return->id)->value('amount'), 'paid refund amount');
            },
            '12. Unbalanced posting rejected; ledger entries immutable' => function (): void {
                $this->delivered();
                $this->blocked(fn () => app(LedgerService::class)->post((string) Str::uuid(), 'verification', null, 'Must reject', [
                    ['owner_type' => 'platform', 'owner_id' => 0, 'account_type' => 'cod_held', 'direction' => 'debit', 'cents' => 100],
                ]), InvalidArgumentException::class, 'unbalanced posting');
                $id = DB::table('ledger_entries')->where('transaction_id', '>', $this->firstTransactionId)->value('id');
                $this->blocked(fn () => DB::table('ledger_entries')->where('id', $id)->update(['amount' => '1.00']), QueryException::class, 'ledger entry update');
                $this->blocked(fn () => DB::table('ledger_entries')->where('id', $id)->delete(), QueryException::class, 'ledger entry delete');
            },
            '13. Debit/credit and per-order reconciliation' => function (): void {
                $first = $this->delivered();
                $this->confirmReceipt($first);
                $this->collectAndRemit($first);
                $this->withdraw('810.00');
                $this->refund($first->fresh());
                $second = $this->delivered();
                $this->refund($second);
                $this->handover('930.00');
                $this->equal(true, app(OrderReturns::class)->markRefunded($second->returns()->firstOrFail(), $this->admin->id), 'reconciliation refund paid');
                $debits = $credits = 0;
                foreach (DB::table('ledger_transactions')->where('id', '>', $this->firstTransactionId)->get() as $transaction) {
                    $transactionDebits = $transactionCredits = 0;
                    foreach (DB::table('ledger_entries')->where('transaction_id', $transaction->id)->get() as $entry) {
                        if ($entry->direction === 'debit') {
                            $transactionDebits += LedgerService::cents((string) $entry->amount);
                        } else {
                            $transactionCredits += LedgerService::cents((string) $entry->amount);
                        }
                    }
                    $this->equal($transactionDebits, $transactionCredits, 'balanced '.$transaction->reference_no);
                    $debits += $transactionDebits;
                    $credits += $transactionCredits;
                }
                $this->equal($debits, $credits, 'total debits and credits');
                foreach ([$first, $second] as $order) {
                    $sellerNet = $this->postedCredit($order, 'seller', 'pending');
                    $commission = $this->postedCredit($order, 'platform', 'commission_pending');
                    $shipping = $this->postedCredit($order, 'hub', 'shipping_earned');
                    $this->equal(93000, $sellerNet + $commission + $shipping, 'delivery split '.$order->order_number);
                    $this->equal(LedgerService::cents($order->cod_collected_amount), $sellerNet + $commission + $shipping, 'cash reconciliation '.$order->order_number);
                }
            },
            '14. References are random, unique and non-sequential' => function (): void {
                $order = $this->delivered();
                $this->confirmReceipt($order);
                $this->collectAndRemit($order);
                $this->withdraw('810.00');
                $this->refund($order->fresh());
                app(Payouts::class)->payRider($this->hub->id, $this->rider->id, [$order->order_number], null, (string) Str::uuid(), $this->hubUser->id);
                $references = [];
                foreach (['ledger_transactions', 'rider_cash_handovers', 'remittances', 'rider_payouts', 'seller_payouts', 'refund_payouts'] as $table) {
                    $query = DB::table($table);
                    if ($table === 'ledger_transactions') {
                        $query->where('id', '>', $this->firstTransactionId);
                    } elseif ($table === 'seller_payouts') {
                        $query->where('seller_id', $this->seller->id);
                    } elseif ($table === 'refund_payouts') {
                        $query->where('buyer_id', $this->buyer->id);
                    } else {
                        $query->where('hub_id', $this->hub->id);
                    }
                    $rows = $query->get();
                    $this->equal(true, $rows->isNotEmpty(), $table.' references exercised');
                    foreach ($rows as $row) {
                        $this->equal(1, preg_match('/^[A-Z]{2}-[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{10}$/D', $row->reference_no), 'random format '.$table);
                        $references[] = $row->reference_no;
                    }
                }
                $this->equal(count($references), count(array_unique($references)), 'unique persisted references');
                $samples = [];
                foreach (range(1, 128) as $index) {
                    $samples[] = OrderCodes::generate('TX', 10);
                }
                $this->equal(128, count(array_unique($samples)), 'random generator uniqueness');
                $sorted = $samples;
                sort($sorted);
                $this->equal(false, $samples === $sorted || $samples === array_reverse($sorted), 'non-sequential generation');
            },
        ];
    }

    private function order(string $status = Order::STATUS_OUT_FOR_DELIVERY): Order
    {
        $order = new Order;
        $order->forceFill(['seller_id' => $this->seller->id, 'buyer_id' => $this->buyer->id, 'buyer_name' => $this->buyer->name,
            'status' => $status, 'logistics_profile_id' => $this->hub->id, 'current_hub_id' => $this->hub->id,
            'destination_hub_id' => $this->hub->id, 'delivery_rider_id' => $this->rider->id,
            'ship_province' => 'Cebu', 'ship_municipality' => 'Cebu City', 'ship_barangay' => 'Lahug',
            'items_subtotal' => '1000.00', 'voucher_discount' => '100.00', 'shop_voucher_id' => $this->voucher->id,
            'voucher_code' => $this->voucher->code, 'total_amount' => '900.00', 'commission_amount' => '90.00',
            'shipping_fee' => '30.00', 'total' => '930.00'])->save();
        $order->items()->create(['product_id' => $this->product->id, 'product_name' => $this->product->name,
            'quantity' => 1, 'price_at_purchase' => '1000.00', 'original_price' => '1000.00',
            'subtotal' => '1000.00', 'voucher_discount_allocated' => '100.00']);

        return $order;
    }

    private function delivered(): Order
    {
        $order = $this->order();
        $this->equal(true, app(OrderTransitions::class)->delivered($order, $this->riderUser->id,
            proofImage: UploadedFile::fake()->image('proof.jpg', 640, 640)), 'delivery transition');

        return $order->refresh();
    }

    private function confirmReceipt(Order $order): void
    {
        app(OrderController::class)->confirmReceived($this->request($this->buyer), $order, app(ParcelLifecycle::class));
        $this->equal(Order::STATUS_COMPLETED, $order->fresh()->status, 'buyer confirmation');
    }

    private function request(User $user): Request
    {
        $request = Request::create('/', 'POST');
        $request->setUserResolver(fn (): User => $user);

        return $request;
    }

    private function handover(string $amount): object
    {
        return app(MoneyMovements::class)->handover($this->hub->id, $this->rider->id, $amount, 'Verification cash received.', (string) Str::uuid(), $this->hubUser->id);
    }

    private function remit(Order $order): object
    {
        return app(MoneyMovements::class)->submitRemittance($this->hub->id, [$order->order_number], null, (string) Str::uuid(), $this->hubUser->id);
    }

    private function collectAndRemit(Order $order): void
    {
        $this->handover(LedgerService::decimal($this->accountBalance('rider', $this->rider->id, 'cash_on_hand')));
        $remittance = $this->remit($order);
        app(MoneyMovements::class)->decideRemittance($remittance->reference_no, true, null, $this->admin->id);
    }

    private function sellerRequest(string $amount): object
    {
        return app(Payouts::class)->requestSeller($this->seller->id, $amount, $this->methodId, (string) Str::uuid());
    }

    private function withdraw(string $amount): void
    {
        $payout = $this->sellerRequest($amount);
        app(Payouts::class)->decideSeller($payout->reference_no, 'approve', null, $this->admin->id);
        app(Payouts::class)->decideSeller($payout->reference_no, 'pay', null, $this->admin->id);
    }

    private function refund(Order $order): OrderReturn
    {
        $return = app(OrderReturns::class)->open($order, $this->buyer->id, ['reason' => 'missing_item', 'details' => 'Verification refund.',
            'refund_method' => 'gcash', 'refund_account_name' => 'Money Verify', 'refund_account_number' => '09123456789'], $order->items()->get(), []);
        $this->equal(true, app(OrderReturns::class)->approve($return, $this->seller->id), 'return approval');

        return $return->refresh();
    }

    private function accountBalance(string $ownerType, int $ownerId, string $account): int
    {
        $balance = 0;
        $debitNormal = in_array($account, LedgerService::DEBIT_ACCOUNTS, true);
        $entries = DB::table('ledger_entries as e')->join('wallet_accounts as a', 'a.id', '=', 'e.account_id')
            ->where('a.owner_type', $ownerType)->where('a.owner_id', $ownerId)->where('a.account_type', $account)
            ->where('e.transaction_id', '>', $this->firstTransactionId)->get(['e.direction', 'e.amount']);
        foreach ($entries as $entry) {
            $balance += (($entry->direction === 'debit') === $debitNormal ? 1 : -1) * LedgerService::cents((string) $entry->amount);
        }

        return $balance;
    }

    private function balance(string $ownerType, int $ownerId, string $account, int $expected): void
    {
        $this->equal(LedgerService::decimal($expected), LedgerService::decimal($this->accountBalance($ownerType, $ownerId, $account)), $ownerType.'.'.$account);
    }

    private function postedCredit(Order $order, string $ownerType, string $account): int
    {
        $amount = DB::table('ledger_entries as e')->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->join('wallet_accounts as a', 'a.id', '=', 'e.account_id')->where('t.order_id', $order->id)->where('t.type', 'delivery')
            ->where('a.owner_type', $ownerType)->where('a.account_type', $account)->where('e.direction', 'credit')->value('e.amount');

        return LedgerService::cents((string) $amount);
    }

    private function ledgerSnapshot(): string
    {
        return hash('sha256', json_encode([
            DB::table('ledger_transactions')->where('id', '>', $this->firstTransactionId)->orderBy('id')->get(),
            DB::table('ledger_entries')->where('transaction_id', '>', $this->firstTransactionId)->orderBy('id')->get(),
        ], JSON_THROW_ON_ERROR));
    }

    /** @param class-string<Throwable> $exceptionClass */
    private function blocked(Closure $action, string $exceptionClass, string $label): void
    {
        $before = $this->ledgerSnapshot();
        $level = DB::transactionLevel();
        DB::beginTransaction();
        $caught = null;
        try {
            $action();
        } catch (Throwable $exception) {
            $caught = $exception;
        } finally {
            if ($caught !== null) {
                DB::rollBack($level);
            } else {
                DB::commit();
            }
        }
        $this->equal(true, $caught instanceof $exceptionClass, $label.' rejected ('.($caught ? $caught::class : 'accepted').')');
        $this->equal($before, $this->ledgerSnapshot(), $label.' ledger unchanged');
    }

    private function equal(mixed $expected, mixed $actual, string $label): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($label.' expected '.json_encode($expected, JSON_THROW_ON_ERROR).'; actual '.json_encode($actual, JSON_THROW_ON_ERROR));
        }
    }
}
