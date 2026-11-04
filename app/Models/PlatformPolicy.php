<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformPolicy extends Model
{
    public const TERMS_SLUG = 'terms-of-service';

    public const PRIVACY_SLUG = 'privacy-policy';

    protected $fillable = ['slug', 'title', 'body', 'version', 'updated_by'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function currentVersion(string $slug): ?int
    {
        return static::query()->where('slug', $slug)->value('version');
    }

    public function hasPublishedContent(): bool
    {
        return filled($this->title) && filled($this->body);
    }

    /**
     * The admin editor is plain text, so escape it first and then add line
     * breaks. This keeps the JSON/page HTML safe without stripping the
     * author's formatting.
     */
    public function contentHtml(): string
    {
        return nl2br(e($this->body), false);
    }
}
