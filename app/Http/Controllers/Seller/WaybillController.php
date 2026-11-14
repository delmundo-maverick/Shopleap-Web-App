<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Picqer\Barcode\BarcodeGeneratorPNG;

class WaybillController extends Controller
{
    public function show(Request $request, Order $order)
    {
        $this->authorizeOwnership($request, $order);

        abort_unless($order->canViewWaybill(), 422, 'A waybill is available once an order is being prepared.');

        $order->load(['items', 'buyer.buyerProfile', 'seller.sellerProfile']);

        // 1D barcode — for handheld scanners at pickup / sorting / delivery.
        $orderCode = $order->waybill_code;
        $barcodePng = (new BarcodeGeneratorPNG)->getBarcode(
            $orderCode,
            BarcodeGeneratorPNG::TYPE_CODE_128,
            2,
            44
        );
        $barcodeBase64 = base64_encode($barcodePng);

        // QR code — for phone-camera scanning (rider app). Deliberately
        // contains only the order reference, never buyer/seller details.
        $qrWriter = new Writer(new ImageRenderer(new RendererStyle(200, 2), new SvgImageBackEnd));
        $qrBase64 = base64_encode($qrWriter->writeString($order->waybill_qr_code));

        $pdf = Pdf::loadView('seller.orders.waybill', compact('order', 'orderCode', 'barcodeBase64', 'qrBase64'))
            ->setPaper([0, 0, 288, 432]); // 4in x 6in shipping label (72pt/in)

        if (! $order->waybill_printed_at) {
            $order->update(['waybill_printed_at' => now()]);
        }

        return $pdf->stream("waybill-order-{$order->order_number}.pdf");
    }

    public function preview(Request $request, Order $order): View
    {
        $this->authorizeOwnership($request, $order);
        abort_unless($order->canViewWaybill(), 422, 'A waybill is available once an order is being prepared.');

        return view('seller.orders.waybill-preview', compact('order'));
    }

    private function authorizeOwnership(Request $request, Order $order): void
    {
        if ($order->seller_id !== $request->user()->id) {
            abort(403, 'You do not have access to this order.');
        }
    }
}
