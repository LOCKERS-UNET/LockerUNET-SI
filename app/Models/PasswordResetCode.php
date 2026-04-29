<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordResetCode extends Model
{
    // La tabla real se llama 'password_reset_tokens'
    protected $table = 'password_reset_tokens';
    
    // La primary key es 'id' por defecto, pero si usaste 'password_reset_code_id' ajústalo
    // protected $primaryKey = 'password_reset_code_id';
    
    // Campos que se pueden llenar masivamente
    protected $fillable = [
        'user_id',      // 👈🏼 Usar user_id en lugar de email
        'token',        // 👈🏼 El campo real es 'token', no 'code'
        'expires_at',
        'used',
    ];

    // Casts para conversión automática
    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    // El código NO tiene timestamps (solo created_at manual)
    public $timestamps = false;

    // Relación con el usuario
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Verificar si el código expiró
    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    // Verificar si el código ya fue usado
    public function isUsed(): bool
    {
        return $this->used === true;
    }
}