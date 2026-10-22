<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** One in-app notification: a title, a sentence, and where to go when it is opened. */
class ActivityNotification extends Notification
{
    /**
     * @param  string       $kind  e.g. cancel.requested, return.refunded
     * @param  string|null  $url   a path inside the site (never an absolute address)
     */
    public function __construct(
        public string $kind,
        public string $title,
        public string $body,
        public ?string $url = null,
        public array $meta = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'meta' => $this->meta,
        ];
    }
}
