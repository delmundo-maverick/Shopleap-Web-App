<?php

namespace App\Support;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

class TicketPresenter
{
    public static function number(Conversation $c): ?string
    {
        return $c->category ? sprintf('SL-%06d', $c->id) : null;
    }

    public static function topic(Conversation $c): ?string
    {
        return $c->category
            ? config("support.topics.{$c->category}.label", ucfirst(str_replace('_', ' ', $c->category)))
            : null;
    }

    /**
     * Display state for a ticket, from the viewer's point of view.
     * Stored status stays open/resolved; everything else is derived.
     *
     * @return array{0:string,1:string} [label, tailwind classes]
     */
    public static function state(Conversation $c, ?Message $last, User $viewer): array
    {
        if ($c->status === 'resolved') {
            return ['Resolved', 'bg-leaf-green/10 text-leaf-green'];
        }

        $fromStaff = $last?->sender?->role === 'super_admin';

        if ($viewer->role === 'super_admin') {
            if (!$c->assigned_to) {
                return ['Unassigned', 'bg-warning/10 text-warning'];
            }
            return $fromStaff
                ? ['Waiting on customer', 'bg-charcoal/10 text-charcoal/60']
                : ['Needs reply', 'bg-primary/10 text-primary'];
        }

        if ($fromStaff) {
            return ['New reply', 'bg-primary/10 text-primary'];
        }

        return $c->assigned_to
            ? ['In progress', 'bg-sky-blue/15 text-sky-blue']
            : ['Submitted', 'bg-warning/10 text-warning'];
    }
}
