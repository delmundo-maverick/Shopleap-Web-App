<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ReviewMedia extends Model
{
    public const DISK = 'r2'; //

    protected $fillable = ['review_id', 'path', 'type', 'sort_order'];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    public function getUrlAttribute(): string
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk(self::DISK);

        return $disk->url($this->path);
    }

    public function getIsVideoAttribute(): bool
    {
        return $this->type === 'video';
    }
}
