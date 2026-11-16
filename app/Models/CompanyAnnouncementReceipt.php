<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\DatabaseNotification;

class CompanyAnnouncementReceipt extends Model
{
    protected $fillable = ['company_announcement_id', 'user_id', 'notification_id', 'read_at', 'dismissed_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'dismissed_at' => 'datetime'];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(CompanyAnnouncement::class, 'company_announcement_id');
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(DatabaseNotification::class, 'notification_id');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->where(fn (Builder $read) => $read->whereNotNull('read_at')->orWhereHas('notification', fn (Builder $notifications) => $notifications->whereNotNull('read_at')));
    }
}
