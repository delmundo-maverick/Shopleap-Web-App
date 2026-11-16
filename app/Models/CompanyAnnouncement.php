<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CompanyAnnouncement extends Model
{
    protected $fillable = ['company_id', 'title', 'body', 'created_by'];

    protected static function booted(): void
    {
        static::creating(function (self $announcement): void {
            $announcement->public_code = (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_code';
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(CompanyAnnouncementReceipt::class);
    }
}
