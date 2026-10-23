<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    protected $primaryKey = 'request_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'building_id',
        'status',
        'request_date',
        'response_date',
        'observations',
    ];

    protected $casts = [
        'request_date' => 'datetime',
        'response_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id', 'building_id');
    }

    public function assignment()
    {
        return $this->hasOne(Assignment::class, 'request_id', 'request_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'request_id', 'request_id');
    }
}
