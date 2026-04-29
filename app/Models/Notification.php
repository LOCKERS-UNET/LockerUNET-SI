<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Este modelo representa una notificación enviada a un usuario
class Notification extends Model
{
    protected $primaryKey = 'notification_id';

    // Solo tiene created_at, no updated_at
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'notification_type',
        'title',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // La notificación pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
