<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'port',
        'version',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 