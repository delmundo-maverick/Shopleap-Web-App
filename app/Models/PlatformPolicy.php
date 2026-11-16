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
     * Render numbered sections from the plain-text editor. Escape all authored
     * content so both public pages and registration dialogs remain safe.
     */
    public function contentHtml(int $headingLevel = 2): string
    {
        $headingLevel = max(2, min(6, $headingLevel));
        $blocks = preg_split('/\R[\t ]*\R/', trim($this->body)) ?: [];

        return implode("\n", array_map(function (string $block) use ($headingLevel): string {
            $parts = preg_split('/\R/', $block, 2);
            if (preg_match('/^\d+\.\s+\S/', $parts[0])) {
                $heading = '<h'.$headingLevel.' class="mb-3 text-lg font-semibold text-charcoal">'.e($parts[0]).'</h'.$headingLevel.'>';
                $body = isset($parts[1]) ? '<p>'.nl2br(e($parts[1]), false).'</p>' : '';

                return '<section class="mt-6">'.$heading.$body.'</section>';
            }

            return '<p class="mt-4">'.nl2br(e($block), false).'</p>';
        }, $blocks));
    }
}
