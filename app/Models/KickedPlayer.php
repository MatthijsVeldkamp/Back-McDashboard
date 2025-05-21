<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KickedPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'uuid',
        'username',
        'reason'
    ];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
