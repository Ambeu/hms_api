<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgrammeFidelite extends Model
{
    use HasFactory;

    protected $fillable = [
        'niveau', 'points_min', 'points_max', 'remise_pct', 'avantages',
    ];

    protected $casts = [
        'avantages' => 'array',
        'remise_pct' => 'decimal:2',
    ];
}
