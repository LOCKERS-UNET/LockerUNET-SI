<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Multa extends Model
{
    // Indicar el nombre real de la tabla
    protected $table = 'fines';

    // Indicar la clave primaria personalizada
    protected $primaryKey = 'fine_id';

    protected $fillable = [
        'assignment_id',
        'user_id',
        'amount', // Antes 'monto'
        'reason', // Antes 'descripcion'
        'status',
    ];
}
