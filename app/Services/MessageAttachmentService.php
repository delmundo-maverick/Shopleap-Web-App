<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class MessageAttachmentService
{
    public const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    public const VIDEO_MIMES = ['video/mp4', 'video/quicktime', 'video/webm'];

    public static function disk(): string
    {
        return config('support.attachments.disk');
    }

    /**
     * Validation rules to merge into any request that accepts attachments.
     * Pass true to also accept videos (chat only; tickets stay photo-only).
     */
    public static function rules(bool $allowVideo = false): array
    {
        $max = (int) config('support.attachments.max_files');
        $imageKb = (int) config('support.attachments.max_kb');
        $videoKb = (int) config('support.attachments.max_video_kb', 20480);

        $mimes = $allowVideo
            ? array_merge(self::IMAGE_MIMES, self::VIDEO_MIMES)
            : self::IMAGE_MIMES;

        return [
            'images' => ['nullable', 'array', "max:{$max}"],
            'images.*' => [
                'bail',
                'file',
                'mimetypes:' . implode(',', $mimes),
                // Per-type size cap (Laravel's `max` can't differ by mime type).
                function (string $attribute, mixed $value, \Closure $fail) use ($imageKb, $videoKb) {
                    if (!$value instanceof UploadedFile) {
                        return;
                    }
                    $isVideo = str_starts_with((string) $value->getMimeType(), 'video/');
                    $limitKb = $isVideo ? $videoKb : $imageKb;

                    if ($value->getSize() > $limitKb * 1024) {
                        $fail(sprintf(
                            '%s is too large (max %d MB for %s).',
                            $value->getClientOriginalName(),
                            (int) round($limitKb / 1024),
                            $isVideo ? 'videos' : 'photos'
                        ));
                    }
                },
            ],
        ];
    }

    public static function store(Message $message, array $files): void
    {
        foreach ($files as $file) {
            $mime = $file->getMimeType();
            $size = $file->getSize();
            $name = Str::limit($file->getClientOriginalName(), 120, '');

            $path = $file->store('message-attachments/' . now()->format('Y/m'), self::disk());

            MessageAttachment::create([
                'message_id' => $message->id,
                'disk' => self::disk(),
                'path' => $path,
                'original_name' => $name,
                'mime' => $mime,
                'size' => $size,
            ]);
        }
    }

    /** message_id => [ {id, url, name, type, mime, size}, ... ] */
    public static function forMessages($messageIds): array
    {
        return MessageAttachment::whereIn('message_id', $messageIds)
            ->orderBy('id')
            ->get()
            ->groupBy('message_id')
            ->map(fn($group) => $group->map(fn($a) => [
                'id' => $a->id,
                'url' => route('messages.attachment', $a),
                'name' => $a->original_name,
                'mime' => $a->mime,
                'size' => $a->size,
                'type' => str_starts_with((string) $a->mime, 'video/') ? 'video' : 'image',
            ])->values()->all())
            ->all();
    }
}
