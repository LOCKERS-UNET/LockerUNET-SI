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

    // La tarifa fue creada por un admin
    public function admin()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
