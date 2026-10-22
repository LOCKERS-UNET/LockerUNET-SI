<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Este modelo representa una tarifa/arancel según el tipo de locker
class FeeRate extends Model
{
    protected $primaryKey = 'rate_id';

    // Solo tiene created_at, no updated_at
    public $timestamps = false;

    protected $fillable = [
        'locker_type',
        'monthly_amount',
        'effective_from',
        'reason',
        'created_by',
    ];

    // Tarifa que rige hoy para un tipo de locker. Si hay dos del mismo día gana la última
    // creada (rate_id): antes se cobraba una u otra al azar y la página mostraba la última
    public static function vigente(string $lockerType): ?self
    {
        return static::where('locker_type', $lockerType)
            ->where('effective_from', '<=', today())
            ->orderBy('effective_from', 'desc')
            ->orderBy('rate_id', 'desc')
            ->first();
    }

    // La tarifa fue creada por un admin
    public function admin()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
