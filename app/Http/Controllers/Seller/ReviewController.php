<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{

    private const REPLY_TEMPLATES = [
        // 4–5 star reviews
        'positive' => [
            [
                'label' => 'Thank you',
                'text'  => 'Thank you so much for your kind review! We are happy you enjoyed your {product}. We hope to serve you again soon.',
            ],
            [
                'label' => 'Salamat po',
                'text'  => 'Salamat po for shopping with {shop}! We are glad you loved your order. See you again on your next purchase!',
            ],
            [
                'label' => 'Appreciate support',
                'text'  => 'We really appreciate your feedback and your support for our shop. Your review motivates us to keep delivering quality products. Looking forward to your next order!',
            ],
            [
                'label' => 'Great rating',
                'text'  => 'Thank you for the great rating! We will keep working to give every customer the same experience. Enjoy your {product}!',
            ],
        ],

        // 1–3 star reviews
        'negative' => [
            [
                'label' => 'Apologize & offer help',
                'text'  => 'We are sorry that your {product} did not meet your expectations. Please message us through chat so we can make this right for you.',
            ],
            [
                'label' => 'Replacement / refund',
                'text'  => 'Thank you for your honest feedback, and we apologize for the inconvenience. Please contact us through chat with your order number so we can help with a replacement or refund.',
            ],
            [
                'label' => 'Sorry po',
                'text'  => 'Sorry po for the trouble with your order. We would love a chance to fix this. Kindly send us a message with your order number and photos so we can assist you right away.',
            ],
            [
                'label' => 'Will improve',
                'text'  => 'We appreciate you telling us about this. We will use your feedback to improve our products and packaging, and we would like to resolve this with you directly through chat.',
            ],
        ],
    ];

    public function index(Request $request)
    {
        $user = $request->user();

        $rating = (int) $request->input('rating');
        $rating = ($rating >= 1 && $rating <= 5) ? $rating : null;

        $productId = (int) $request->input('product') ?: null;

        $status = in_array($request->input('status'), ['unreplied', 'replied'], true)
            ? $request->input('status')
            : null;

        // Unfiltered totals for the summary strip and the status pills.
        $summary = Review::where('seller_id', $user->id)
            ->toBase()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('AVG(rating) AS average')
            ->selectRaw('SUM(CASE WHEN seller_reply IS NULL THEN 1 ELSE 0 END) AS unreplied')
            ->first();

        $total = (int) ($summary->total ?? 0);
        $unreplied = (int) ($summary->unreplied ?? 0);

        $stats = [
            'count'     => $total,
            'average'   => $total > 0 ? round((float) $summary->average, 1) : null,
            'unreplied' => $unreplied,
            'replied'   => $total - $unreplied,
        ];

        $query = Review::with(['media', 'orderItem', 'product', 'buyer'])
            ->where('seller_id', $user->id)
            ->latest();

        if ($rating) {
            $query->where('rating', $rating);
        }

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($status === 'unreplied') {
            $query->whereNull('seller_reply');
        } elseif ($status === 'replied') {
            $query->whereNotNull('seller_reply');
        }

        $reviews = $query->paginate(10)->withQueryString()->through(fn(Review $review) => [
            'id'         => $review->id,
            'rating'     => $review->rating,
            'comment'    => $review->comment,
            'product'    => $review->orderItem?->product_name ?? $review->product?->name ?? 'Product',
            'buyer'      => $review->buyer_masked_name,
            'order_id'   => $review->order_id,
            'created_at' => $review->created_at,
            'reply'      => $review->seller_reply,
            'replied_at' => $review->seller_replied_at,
            'media'      => $review->media->map(fn($m) => [
                'url'      => $m->url,
                'is_video' => $m->is_video,
            ])->values(),
        ]);

        // Products that actually have reviews, for the filter dropdown.
        $productOptions = Product::where('seller_id', $user->id)
            ->whereHas('reviews')
            ->orderBy('name')
            ->get(['id', 'name']);

        $sellerProfile = $user->sellerProfile;

        return view('seller.reviews.index', compact(
            'sellerProfile',
            'reviews',
            'stats',
            'productOptions',
            'rating',
            'productId',
            'status'
        ))->with('replyTemplates', self::REPLY_TEMPLATES);
    }

    /** Post a reply, or edit the existing one. */
    public function reply(Request $request, Review $review)
    {
        $this->authorizeOwnership($request, $review);

        // Each review's form has its own error bag so one failure doesn't light up every form.
        $data = $request->validateWithBag("reply_{$review->id}", [
            'reply' => ['required', 'string', 'min:2', 'max:500'],
        ]);

        $isEdit = filled($review->seller_reply);

        $review->update([
            'seller_reply'      => trim($data['reply']),
            'seller_replied_at' => now(),
        ]);

        return back()->with('status', $isEdit ? 'Your reply has been updated.' : 'Your reply has been posted.');
    }

    public function destroyReply(Request $request, Review $review)
    {
        $this->authorizeOwnership($request, $review);

        $review->update([
            'seller_reply'      => null,
            'seller_replied_at' => null,
        ]);

        return back()->with('status', 'Your reply has been deleted.');
    }

    private function authorizeOwnership(Request $request, Review $review): void
    {
        if ($review->seller_id !== $request->user()->id) {
            abort(403);
        }
    }
}
