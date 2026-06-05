<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etage extends Model
{
    use HasFactory;

    protected $fillable = [
        'etablissement_id', 'numero', 'nom', 'nb_chambres',
    ];

    protected $casts = [
        'numero' => 'integer',
        'nb_chambres' => 'integer',
    ];

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function chambres()
    {
        return $this->hasMany(Chambre::class);
    }
}
