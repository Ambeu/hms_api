<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CanalDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom', 'type', 'api_endpoint', 'api_key_hash', 'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    protected $hidden = ['api_key_hash'];
}
