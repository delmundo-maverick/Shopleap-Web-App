<?php

namespace App\Console\Commands;

use App\Models\DeliveryProof;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

#[Signature('delivery-proofs:expire')]
#[Description('Delete retained delivery proof images while preserving expired records')]
class ExpireDeliveryProofs extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $failed = false;
        DeliveryProof::whereNull('expired_at')
            ->where('uploaded_at', '<', now()->subDays(max(1, (int) config('delivery_proofs.retention_days'))))
            ->chunkById(100, function (Collection $proofs) use (&$failed): void {
                foreach ($proofs as $proof) {
                    try {
                        if (! Storage::disk('r2')->delete([$proof->image_path, $proof->thumbnail_path])) {
                            throw new \RuntimeException('Deletion failed');
                        }
                        $proof->update(['expired_at' => now()]);
                    } catch (\Throwable) {
                        $failed = true;
                        $this->error('A delivery proof could not be expired; it will be retried.');
                    }
                }
            });

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
