<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SellerProductImages
{
    private array $uploaded = [];

    private array $obsolete = [];

    public function sync(Request $request, Product $product): void
    {
        foreach ($product->images()->whereKey($request->input('remove_images', []))->get() as $image) {
            $this->obsolete[] = $image->path;
            $image->delete();
        }
        $sortOrder = (int) $product->images()->max('sort_order');
        foreach ($request->file('images', []) as $file) {
            $path = $file->store('product_images', 'r2_public');
            if (! $path) {
                throw new RuntimeException('The image could not be uploaded. Please try again.');
            }
            $this->uploaded[] = $path;
            $product->images()->create(['path' => $path, 'sort_order' => ++$sortOrder]);
        }
        $variants = $product->variants()->orderBy('id')->get();
        foreach ($request->input('variants', []) as $index => $row) {
            $variant = isset($row['id']) ? $variants->firstWhere('id', $row['id']) : $variants->values()->get($index);
            if (! $variant) {
                continue;
            }
            $file = $request->file("variants.{$index}.image");
            if (! $file && empty($row['remove_image'])) {
                continue;
            }
            $path = $file ? $file->store('variant_images', 'r2_public') : null;
            if ($file && ! $path) {
                throw new RuntimeException('The variant image could not be uploaded. Please try again.');
            }
            if ($path) {
                $this->uploaded[] = $path;
            }
            if ($variant->image_path) {
                $this->obsolete[] = $variant->image_path;
            }
            $variant->update(['image_path' => $path]);
        }
    }

    public function discardUploads(): void
    {
        $this->deleteUnused($this->uploaded);
    }

    public function finish(): void
    {
        $this->deleteUnused($this->obsolete);
    }

    /** Duplicated drafts may share images; delete only files with no remaining references. */
    public function deleteUnused(array $paths): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            if (ProductImage::where('path', $path)->exists() || ProductVariant::where('image_path', $path)->exists()) {
                continue;
            }
            try {
                Storage::disk('r2_public')->delete($path);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
