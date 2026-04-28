<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailVerificationCode extends Model
{
    protected $table = 'email_verification_codes';
    
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    public function isUsed(): bool
    {
        return $this->used === true;
    }
}