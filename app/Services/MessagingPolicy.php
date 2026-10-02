<?php

namespace App\Services;

use App\Models\User;

class MessagingPolicy
{
    public static function canMessage(User $a, User $b): bool
    {
        if ($a->id === $b->id) {
            return false;
        }

        $allowed = config('messaging_permissions');

        return in_array($b->role, $allowed[$a->role] ?? [], true)
            || in_array($a->role, $allowed[$b->role] ?? [], true);
    }

    public static function allowedRolesFor(User $user): array
    {
        return config('messaging_permissions')[$user->role] ?? [];
    }
}
