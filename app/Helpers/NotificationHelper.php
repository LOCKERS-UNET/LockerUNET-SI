<?php

namespace App\Helpers;

use App\Models\Notification;

class NotificationHelper
{
    /**
     * Envía una notificación in-app a un usuario.
     *
     * @param int $userId
     * @param string|null $type
     * @param string $title
     * @param string $message
     * @return Notification
     */
    public static function send(int $userId, ?string $type, string $title, string $message)
    {
        return Notification::create([
            'user_id' => $userId,
            'notification_type' => $type,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
        ]);
    }
}
