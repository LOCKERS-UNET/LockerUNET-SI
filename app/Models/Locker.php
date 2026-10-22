<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Este modelo representa un locker físico
class Locker extends Model
{
    use HasFactory;

    protected $primaryKey = 'locker_id';

    protected $fillable = [
        'locker_code',
        'sector_id',
        'locker_type',
        'plate_number',
        'status',
    ];

    // El status se guarda como número: 0=disponible, 1=ocupado, 2=mantenimiento
    protected $casts = [
        'status' => 'integer',
    ];

    // Un locker pertenece a un sector
    public function sector()
    {
        return $this->belongsTo(Sector::class, 'sector_id', 'sector_id');
    }

    // Un locker tiene muchas solicitudes
    public function requests()
    {
        return $this->hasMany(LockerRequest::class, 'locker_id', 'locker_id');
    }

    // Lockers que un estudiante puede pedir: disponibles (status 0) y sin una solicitud pendiente
    // de otra persona (antes dos estudiantes podían esperar por el mismo locker)
    public function scopeSolicitables($query)
    {
        return $query->where('status', 0)
            ->whereDoesntHave('requests', fn ($q) => $q->where('request_status', 'pending'));
    }

    // Un locker tiene muchas asignaciones
    public function assignments()
    {
        return $this->hasMany(LockerAssignment::class, 'locker_id', 'locker_id');
    }
}
