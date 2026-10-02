<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Picqer\Barcode\BarcodeGeneratorPNG;

class WaybillController extends Controller
{
    public function show(Request $request, Order $order)
    {
        $this->authorizeOwnership($request, $order);

        if (!in_array($order->status, [
            Order::STATUS_PREPARING,
            Order::STATUS_READY_FOR_PICKUP,
            Order::STATUS_PICKED_UP,
            Order::STATUS_AT_SORTING_CENTER,
            Order::STATUS_SORTED,
            Order::STATUS_ASSIGNED_TO_RIDER,
            Order::STATUS_OUT_FOR_DELIVERY,
            Order::STATUS_DELIVERED,
            Order::STATUS_COMPLETED,
        ], true)) {
            abort(422, 'A waybill can only be printed once an order is being prepared.');
        }

        $order->load(['items', 'buyer.buyerProfile', 'seller.sellerProfile']);

        // 1D barcode — for handheld scanners at pickup / sorting / delivery.
        $orderCode = str_pad($order->id, 10, '0', STR_PAD_LEFT);
        $barcodePng = (new BarcodeGeneratorPNG())->getBarcode(
            $orderCode,
            BarcodeGeneratorPNG::TYPE_CODE_128,
            2,
            44
        );
        $barcodeBase64 = base64_encode($barcodePng);

        // QR code — for phone-camera scanning (rider app). Deliberately
        // contains only the order reference, never buyer/seller details.
        $qrWriter = new Writer(new ImageRenderer(new RendererStyle(200, 2), new SvgImageBackEnd()));
        $qrBase64 = base64_encode($qrWriter->writeString('SHOPLEAP:ORDER:' . $order->id));

        $pdf = Pdf::loadView('seller.orders.waybill', compact('order', 'orderCode', 'barcodeBase64', 'qrBase64'))
            ->setPaper([0, 0, 288, 432]); // 4in x 6in shipping label (72pt/in)

        if (!$order->waybill_printed_at) {
            $order->update(['waybill_printed_at' => now()]);
        }

        return $pdf->stream("waybill-order-{$order->id}.pdf");
    }

    private function authorizeOwnership(Request $request, Order $order): void
    {
        if ($order->seller_id !== $request->user()->id) {
            abort(403, 'You do not have access to this order.');
        }
    }
}
