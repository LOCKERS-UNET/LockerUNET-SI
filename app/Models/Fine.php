<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Este modelo representa una multa aplicada a un estudiante
class Fine extends Model
{
    protected $primaryKey = 'fine_id';

    // Solo tiene created_at, no updated_at
    public $timestamps = false;

    protected $fillable = [
        'assignment_id',
        'user_id',
        'amount',
        'reason',
        'created_by',
    ];

    // La multa pertenece a una asignación
    public function assignment()
    {
        return $this->belongsTo(LockerAssignment::class, 'assignment_id', 'assignment_id');
    }

    // La multa fue aplicada a un estudiante
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // La multa fue creada por un admin
    public function admin()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
