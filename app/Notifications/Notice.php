<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** In-app notification. Stores a type key + params; the text is rendered per viewer locale. */
class Notice extends Notification
{
    public function __construct(public string $type, public array $params = [], public ?string $url = null) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => $this->type, 'params' => $this->params, 'url' => $this->url];
    }
}
