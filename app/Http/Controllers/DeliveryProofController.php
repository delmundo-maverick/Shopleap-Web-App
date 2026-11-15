<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\HubContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DeliveryProofController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        $this->authorizeProof($request, $order);

        return view('logistics.delivery-proof', ['order' => $order, 'proof' => $order->deliveryProof]);
    }

    public function image(Request $request, Order $order): Response
    {
        $this->authorizeProof($request, $order);
        $proof = $order->deliveryProof;
        if (! $proof || $proof->expired_at) {
            return response($proof ? 'Proof expired' : 'No proof (before feature)', 410)->header('Cache-Control', 'private, no-store');
        }
        try {
            $stream = Storage::disk('r2')->readStream($request->boolean('thumbnail') ? $proof->thumbnail_path : $proof->image_path);
            if (! is_resource($stream)) {
                throw new \RuntimeException('Unavailable');
            }
        } catch (\Throwable) {
            return response('Image not available', 404)->header('Cache-Control', 'private, no-store');
        }

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Content-Disposition' => 'inline; filename="delivery-proof.jpg"']);
    }

    private function authorizeProof(Request $request, Order $order): void
    {
        $user = $request->user();
        abort_unless($user && ($user->role === 'super_admin' || ($user->role === 'logistics' && $user->account_status === 'active' && $user->logisticsProfile?->status === 'approved')), 403);
        $order->loadMissing('deliveryProof.courier');
        $hubId = $order->deliveryProof?->logistics_profile_id ?? $order->earning_hub_id ?? $order->current_hub_id ?? $order->logistics_profile_id;
        abort_unless($user->role === 'super_admin' || (int) HubContext::id($request) === (int) $hubId, 403);
    }
}
