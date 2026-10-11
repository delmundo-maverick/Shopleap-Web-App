<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ReviewMedia extends Model
{
    public const DISK = 'r2'; // match the disk your ProductImage uses

    protected $fillable = ['review_id', 'path', 'type', 'sort_order'];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk(self::DISK)->url($this->path);
    }

    public function getIsVideoAttribute(): bool
    {
        return $this->type === 'video';
    }
}
