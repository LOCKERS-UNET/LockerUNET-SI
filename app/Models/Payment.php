<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Este modelo representa un pago de arancel por una asignación de locker
class Payment extends Model
{
    protected $primaryKey = 'payment_id';

    // Solo tiene created_at, no updated_at
    public $timestamps = false;

    protected $fillable = [
        'assignment_id',
        'user_id',
        'amount',
        'due_date',
        'payment_status',
        'semester',
    ];

    // El pago pertenece a una asignación
    public function assignment()
    {
        return $this->belongsTo(LockerAssignment::class, 'assignment_id', 'assignment_id');
    }

    // El pago lo hizo un estudiante
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
