<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\ReviewMedia;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    public function store(Request $request, Order $order, OrderItem $item)
    {
        abort_if($order->buyer_id !== $request->user()->id, 403);
        abort_if($item->order_id !== $order->id, 404);

        if ($order->status !== Order::STATUS_COMPLETED) {
            return $this->respond($request, 'You can review items once the order is completed.', 422);
        }

        if ($item->review()->exists()) {
            return $this->respond($request, 'You already reviewed this item.', 422);
        }

        $data = $request->validate([
            'rating'   => ['required', 'integer', 'between:1,5'],
            'comment'  => ['nullable', 'string', 'max:1000'],
            'media'    => ['nullable', 'array', 'max:5'],
            'media.*'  => [
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime',
                'max:20480', // KB
            ],
        ]);

        $uploaded = [];

        try {
            DB::transaction(function () use ($request, $order, $item, $data, &$uploaded) {
                $review = Review::create([
                    'order_item_id' => $item->id,
                    'order_id'      => $order->id,
                    'product_id'    => $item->product_id,
                    'buyer_id'      => $request->user()->id,
                    'seller_id'     => $order->seller_id,
                    'rating'        => $data['rating'],
                    'comment'       => filled($data['comment'] ?? null) ? trim($data['comment']) : null,
                ]);

                foreach ($request->file('media', []) as $i => $file) {
                    $name = Str::uuid() . '.' . $file->extension();
                    $path = Storage::disk(ReviewMedia::DISK)
                        ->putFileAs("reviews/{$review->id}", $file, $name);

                    if (! $path) {
                        throw new \RuntimeException('Media upload failed.');
                    }

                    $uploaded[] = $path;

                    $review->media()->create([
                        'path'       => $path,
                        'type'       => str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image',
                        'sort_order' => $i,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            // Don't leave orphaned files in R2 if the DB write failed.
            if ($uploaded) {
                Storage::disk(ReviewMedia::DISK)->delete($uploaded);
            }

            if ($e instanceof UniqueConstraintViolationException) {
                return $this->respond($request, 'You already reviewed this item.', 422);
            }

            report($e);

            return $this->respond($request, 'We couldn\'t save your review. Please try again.', 500);
        }

        $message = 'Thanks for your review!';
        $request->session()->flash('status', $message);

        return $this->respond($request, $message, 200);
    }

    private function respond(Request $request, string $message, int $code)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $code);
        }

        return back()->with('status', $message);
    }
}
