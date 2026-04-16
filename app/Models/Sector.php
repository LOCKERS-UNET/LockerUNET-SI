<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Este modelo representa un sector dentro de un edificio (ej: Piso 1, Planta Baja)
class Sector extends Model
{
    protected $primaryKey = 'sector_id';

    // Esta tabla NO tiene created_at ni updated_at
    public $timestamps = false;

    protected $fillable = [
        'building_id',
        'sector_name',
    ];

    // Un sector pertenece a un edificio
    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id', 'building_id');
    }

    // Un sector tiene muchos lockers
    public function lockers()
    {
        return $this->hasMany(Locker::class, 'sector_id', 'sector_id');
    }
}
