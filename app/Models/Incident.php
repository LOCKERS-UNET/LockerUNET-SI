<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Este modelo representa una incidencia reportada por un estudiante sobre su locker
class Incident extends Model
{
    protected $primaryKey = 'incident_id';

    // Solo tiene created_at, no updated_at
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'locker_id',
        'description',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    // La incidencia fue reportada por un estudiante
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // La incidencia es sobre un locker específico
    public function locker()
    {
        return $this->belongsTo(Locker::class, 'locker_id', 'locker_id');
    }

    // La incidencia fue revisada por un admin
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id');
    }
}
