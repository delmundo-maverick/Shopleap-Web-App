<?php

namespace App\Http\Controllers;

use App\Models\OrderReturnMedia;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReturnMediaController extends Controller
{
    /** Evidence is private: only the buyer, the seller and an admin can open it. */
    public function show(Request $request, OrderReturnMedia $returnMedia)
    {
        $return = $returnMedia->orderReturn;
        $user = $request->user();

        abort_unless(
            in_array($user->id, [$return->buyer_id, $return->seller_id], true) || $user->role === 'super_admin',
            403
        );

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(OrderReturnMedia::DISK);

        abort_unless($disk->exists($returnMedia->path), 404);

        // Preferred: after the check above, hand the browser a link that only works for 10 minutes.
        // R2/S3 serve it directly, including the range requests videos need.
        try {
            return redirect()->away($disk->temporaryUrl($returnMedia->path, now()->addMinutes(10)));
        } catch (\RuntimeException $e) {
            // This disk can't sign URLs (for example a local disk): stream the file instead.
        }

        return $disk->response($returnMedia->path, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
