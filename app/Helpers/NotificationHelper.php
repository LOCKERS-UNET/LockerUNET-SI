<?php

namespace App\Helpers;

use App\Models\Notification;
use App\Models\User;

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

    /**
     * Envía la misma notificación a todos los administradores
     * (solicitudes nuevas, devoluciones, incidencias...).
     */
    public static function sendToAdmins(?string $type, string $title, string $message): void
    {
        User::where('is_admin', true)->pluck('id')
            ->each(fn ($adminId) => self::send($adminId, $type, $title, $message));
    }

    /** "Ana Pérez (30001)", para los avisos a los admins. */
    public static function nombreEstudiante(User $user): string
    {
        return trim("{$user->name} {$user->lastname}").($user->card_code ? " ({$user->card_code})" : '');
    }
}
