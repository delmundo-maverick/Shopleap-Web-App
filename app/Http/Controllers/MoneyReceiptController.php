<?php

namespace App\Http\Controllers;

use App\Services\MoneyReceipts;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MoneyReceiptController extends Controller
{
    public function show(Request $request, string $type, string $reference, MoneyReceipts $receipts): Response
    {
        return response()->view('money.receipt', $receipts->receipt($request->user(), $type, $reference))
            ->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
