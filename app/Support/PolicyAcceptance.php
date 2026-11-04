<?php

namespace App\Support;

use App\Models\PlatformPolicy;

final class PolicyAcceptance
{
    /**
     * Attributes recording the policy versions a registering user accepted.
     *
     * @return array{
     *     terms_version: int|null,
     *     terms_accepted_at: string,
     *     privacy_version: int|null,
     *     privacy_accepted_at: string
     * }
     */
    public static function attributes(): array
    {
        $acceptedAt = now()->toDateTimeString();

        return [
            'terms_version' => PlatformPolicy::currentVersion(PlatformPolicy::TERMS_SLUG),
            'terms_accepted_at' => $acceptedAt,
            'privacy_version' => PlatformPolicy::currentVersion(PlatformPolicy::PRIVACY_SLUG),
            'privacy_accepted_at' => $acceptedAt,
        ];
    }
}
