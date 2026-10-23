<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    // Carreras que se ofrecen en el registro y el perfil (se validan con 'in:')
    public const CARRERAS = [
        'Informática', 'Industrial', 'Civil', 'Electrónica', 'Mecánica', 'Psicología',
        'TSU Entrenamiento Deportivo', 'Producción Animal', 'Ambiental', 'Agronomía',
        'Música', 'Agroindustrial', 'Arquitectura',
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $fillable = [
        'name',
        'lastname',
        'email',
        'card_code',
        'career',
        'password',
        'email_verified_at',
    ];

    // Relaciones
    public function assignments()
    {
        return $this->hasMany(LockerAssignment::class, 'user_id', 'id');
    }

    public function requests()
    {
        return $this->hasMany(LockerRequest::class, 'user_id', 'id');
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class, 'user_id', 'id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'user_id', 'id');
    }

    public function fines()
    {
        return $this->hasMany(Fine::class, 'user_id', 'id');
    }
}
