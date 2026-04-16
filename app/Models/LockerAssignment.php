<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Este modelo representa una asignación de locker (cuando ya fue aprobada y asignada)
class LockerAssignment extends Model
{
    protected $table = 'locker_assignments';

    protected $primaryKey = 'assignment_id';

    protected $fillable = [
        'user_id',
        'locker_id',
        'request_id',
        'start_date',
        'end_date',
        'assignment_status',
        'created_by',
    ];

    // La asignación pertenece a un estudiante
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // La asignación es para un locker
    public function locker()
    {
        return $this->belongsTo(Locker::class, 'locker_id', 'locker_id');
    }

    // La asignación vino de una solicitud
    public function request()
    {
        return $this->belongsTo(LockerRequest::class, 'request_id', 'request_id');
    }

    // La asignación fue creada por un admin
    public function admin()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    // Una asignación puede tener varios pagos
    public function payments()
    {
        return $this->hasMany(Payment::class, 'assignment_id', 'assignment_id');
    }

    // Una asignación puede tener varias multas
    public function fines()
    {
        return $this->hasMany(Fine::class, 'assignment_id', 'assignment_id');
    }
}
