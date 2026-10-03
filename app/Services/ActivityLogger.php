<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * Record a platform event. $subject is optional (e.g. the Order, SellerProfile,
     * or CourierProfile the action was about) — its class/ID are stored so the log
     * can be filtered or linked back later, and the actor is pulled from auth()
     * unless the action is system-generated (no one logged in).
     */
    public static function log(string $action, string $category, string $description, ?Model $subject = null, ?array $properties = null): ActivityLog
    {
        $user = auth()->user();

        return ActivityLog::create([
            'actor_id' => $user?->id,
            'actor_name' => $user?->name,
            'actor_role' => $user?->role,
            'action' => $action,
            'category' => $category,
            'description' => $description,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties,
        ]);
    }
}
