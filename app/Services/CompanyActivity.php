<?php

namespace App\Services;

use App\Models\CompanyActivityLog;
use Illuminate\Database\Eloquent\Model;

class CompanyActivity
{
    public const ACTIONS = [
        'hub.created' => 'Hub created', 'hub.edited' => 'Hub edited', 'hub.suspended' => 'Hub suspended', 'hub.reactivated' => 'Hub reactivated',
        'manager.edited' => 'Manager edited', 'manager.suspended' => 'Manager login suspended', 'manager.reactivated' => 'Manager login reactivated',
        'manager.invited' => 'Manager invited', 'manager.invite_resent' => 'Manager invite resent', 'manager.reset_sent' => 'Manager reset link sent', 'manager.logged_out' => 'Manager forced logout',
        'settings.updated' => 'Company settings changed', 'settings.password_changed' => 'Company password changed', 'announcement.created' => 'Announcement posted',
    ];

    /** @param array{fields?: list<string>, recipients?: int} $meta */
    public static function record(int $companyId, string $action, Model $subject, array $meta = []): void
    {
        $user = auth()->user();
        abort_unless($user?->role === 'logistics_company' && (int) $user->company_id === $companyId, 403);
        $safe = [];
        if (isset($meta['fields'])) {
            $safe['fields'] = array_values(array_intersect($meta['fields'], ['name', 'address', 'contact_phone', 'contact_email', 'logo_path', 'default_rider_rate']));
        }
        if (isset($meta['recipients'])) {
            $safe['recipients'] = (int) $meta['recipients'];
        }
        CompanyActivityLog::create(['company_id' => $companyId, 'user_id' => $user->id, 'action' => $action,
            'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey(), 'meta' => $safe,
            'ip_address' => request()->ip(), 'created_at' => now()]);
    }
}
