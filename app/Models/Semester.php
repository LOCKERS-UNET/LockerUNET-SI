<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    protected $fillable = [
        'name',
        'start_month',
        'start_year',
        'end_month',
        'end_year',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Obtener fecha de inicio como Carbon
    public function getStartDateAttribute()
    {
        return Carbon::create($this->start_year, $this->start_month, 1);
    }

    // Obtener fecha de fin como Carbon
    public function getEndDateAttribute()
    {
        return Carbon::create($this->end_year, $this->end_month, 1)->endOfMonth();
    }
}
