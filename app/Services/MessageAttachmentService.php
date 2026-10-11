<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Support\Str;


class MessageAttachmentService
{
    public static function disk(): string
    {
        return config('support.attachments.disk');
    }

    /** Validation rules to merge into any request that accepts photos. */
    public static function rules(): array
    {
        $max = (int) config('support.attachments.max_files');
        $kb = (int) config('support.attachments.max_kb');

        return [
            'images' => ['nullable', 'array', "max:{$max}"],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp,gif', "max:{$kb}"],
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

    /** message_id => [ {id, url, name}, ... ] */
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
            ])->values()->all())
            ->all();
    }
}
