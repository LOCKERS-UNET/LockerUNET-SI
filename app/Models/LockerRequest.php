<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Este modelo representa una solicitud de locker hecha por un estudiante
class LockerRequest extends Model
{
    // Le decimos que la tabla se llama "locker_requests" (Laravel asumiría "locker_requests" solo si el modelo se llama LockerRequest, pero es bueno ser explícito)
    protected $table = 'locker_requests';

    protected $primaryKey = 'request_id';

    // Solo tenemos "requested_at", no created_at/updated_at estándar
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'locker_id',
        'request_status',
        'requested_at',
        'reviewed_by',
        'reviewed_at',
    ];

    // La solicitud pertenece a un estudiante (usuario)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // La solicitud es para un locker específico
    public function locker()
    {
        return $this->belongsTo(Locker::class, 'locker_id', 'locker_id');
    }

    // La solicitud fue revisada por un admin (también es un usuario)
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id');
    }
}
