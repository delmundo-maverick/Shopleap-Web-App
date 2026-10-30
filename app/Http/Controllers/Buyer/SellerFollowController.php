<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SellerFollowController extends Controller
{
    public function store(Request $request, User $seller): JsonResponse|RedirectResponse
    {
        $buyer = $request->user();

        abort_if($buyer->is($seller), 422, 'You cannot follow your own shop.');
        abort_unless($seller->isEligibleSeller(), 404);

        try {
            $buyer->followedSellers()->syncWithoutDetaching([$seller->id]);
        } catch (UniqueConstraintViolationException) {
            // A concurrent request already created the follow.
        }

        return $this->state(
            $request,
            $seller,
            true,
            "You are now following {$seller->sellerProfile->shop_name}.",
        );
    }

    public function destroy(Request $request, User $seller): JsonResponse|RedirectResponse
    {
        $request->user()->followedSellers()->detach($seller->id);

        return $this->state(
            $request,
            $seller,
            false,
            'You unfollowed '.($seller->sellerProfile?->shop_name ?? $seller->name).'.',
        );
    }

    protected function state(Request $request, User $seller, bool $following, string $message): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return back()->with('status', $message);
        }

        return response()->json([
            'success' => true,
            'following' => $following,
            'followers_count' => $seller->followers()->count(),
            'following_count' => $request->user()->followedSellers()->count(),
            'message' => $message,
        ]);
    }
}
