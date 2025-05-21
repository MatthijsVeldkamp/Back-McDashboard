<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaadSessie extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'socket_id',
        'start_time',
        'stop_time',
        'total_energy_begin',
        'total_energy_end',
        'final_energy'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'stop_time' => 'datetime',
        'total_energy_begin' => 'decimal:3',
        'total_energy_end' => 'decimal:3',
        'final_energy' => 'decimal:3'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
