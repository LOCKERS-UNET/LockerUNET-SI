<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Este modelo representa un edificio (ej: Edificio A, Edificio 6)
class Building extends Model
{
    // Le decimos a Laravel el nombre de la clave primaria (por defecto espera "id")
    protected $primaryKey = 'building_id';

    // Esta tabla NO tiene columnas created_at ni updated_at
    public $timestamps = false;

    // Campos que se pueden guardar/actualizar
    protected $fillable = [
        'building_code',
        'building_name',
    ];

    // Un edificio tiene muchos sectores
    public function sectors()
    {
        return $this->hasMany(Sector::class, 'building_id', 'building_id');
    }
}
