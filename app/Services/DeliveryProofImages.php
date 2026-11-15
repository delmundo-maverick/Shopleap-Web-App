<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeliveryProofImages
{
    /**
     * @param  array<int, string>  $stored
     * @return array{image_path: string, thumbnail_path: string, mime_type: string, size_bytes: int}
     */
    public function store(UploadedFile $file, array &$stored): array
    {
        $bytes = file_get_contents($file->getRealPath());
        $dimensions = @getimagesizefromstring($bytes);
        if (! $dimensions || $dimensions[0] * $dimensions[1] > 40000000) {
            throw ValidationException::withMessages(['proof_image' => 'The photo cannot exceed 40 megapixels.']);
        }
        $image = @imagecreatefromstring($bytes);
        if (! $image) {
            throw ValidationException::withMessages(['proof_image' => 'The photo could not be decoded.']);
        }
        try {
            $scaled = $this->scaled($image, 1600);
            unset($image);
            $image = $scaled;
            unset($scaled);
            $orientation = $this->orientation($bytes);
            if (in_array($orientation, [2, 4, 5, 7], true)) {
                imageflip($image, IMG_FLIP_HORIZONTAL);
            }
            $angle = match ($orientation) {
                3, 4 => 180, 5, 8 => 90, 6, 7 => -90, default => 0
            };
            if ($angle !== 0) {
                $rotated = imagerotate($image, $angle, 0);
                if (! $rotated) {
                    throw new \RuntimeException('Photo processing failed.');
                }
                $image = $rotated;
                unset($rotated);
            }
            $path = 'delivery-proofs/'.now()->format('Y/m').'/'.Str::uuid();
            $jpeg = $this->jpeg($image, 1600);
            $thumbnail = $this->jpeg($image, 400);
            foreach ([$path.'.jpg' => $jpeg, $path.'-thumb.jpg' => $thumbnail] as $key => $content) {
                $stored[] = $key;
                if (! Storage::disk('r2')->put($key, $content, ['visibility' => 'private', 'ContentType' => 'image/jpeg'])) {
                    throw new \RuntimeException('Photo upload failed.');
                }
            }

            return ['image_path' => $path.'.jpg', 'thumbnail_path' => $path.'-thumb.jpg', 'mime_type' => 'image/jpeg', 'size_bytes' => strlen($jpeg)];
        } finally {
            unset($image);
        }
    }

    private function jpeg(\GdImage $image, int $longest): string
    {
        $canvas = $this->scaled($image, $longest);
        ob_start();
        try {
            if (! imagejpeg($canvas, null, 88)) {
                throw new \RuntimeException('Photo encoding failed.');
            }

            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    private function scaled(\GdImage $image, int $longest): \GdImage
    {
        $scale = min(1, $longest / max(imagesx($image), imagesy($image)));
        $width = max(1, (int) round(imagesx($image) * $scale));
        $height = max(1, (int) round(imagesy($image) * $scale));
        $canvas = imagecreatetruecolor($width, $height);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));

        return $canvas;
    }

    /** Read only the TIFF orientation; GD re-encoding discards all source metadata. */
    private function orientation(string $bytes): int
    {
        $position = strpos($bytes, "Exif\0\0");
        if ($position !== false) {
            $tiff = substr($bytes, $position + 6);
        } elseif (($position = strpos($bytes, 'eXIf')) !== false) {
            $tiff = substr($bytes, $position + 4);
        } elseif (($position = strpos($bytes, 'EXIF')) !== false) {
            $tiff = substr($bytes, $position + 8);
        } else {
            return 1;
        }
        $little = substr($tiff, 0, 2) === 'II';
        if (! $little && substr($tiff, 0, 2) !== 'MM') {
            return 1;
        }
        $read = static function (int $offset, int $length) use ($tiff, $little): int {
            if ($offset < 0 || $offset + $length > strlen($tiff)) {
                return 0;
            }

            return unpack($length === 2 ? ($little ? 'v' : 'n') : ($little ? 'V' : 'N'), substr($tiff, $offset, $length))[1];
        };
        if ($read(2, 2) !== 42) {
            return 1;
        }
        $offset = $read(4, 4);
        $count = min($read($offset, 2), 1024);
        for ($i = 0; $i < $count; $i++) {
            $entry = $offset + 2 + $i * 12;
            if ($read($entry, 2) === 274 && $read($entry + 2, 2) === 3 && $read($entry + 4, 4) === 1) {
                $orientation = $read($entry + 8, 2);

                return $orientation >= 1 && $orientation <= 8 ? $orientation : 1;
            }
        }

        return 1;
    }
}
