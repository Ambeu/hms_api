<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeChambre extends Model
{
    use HasFactory;

    protected $fillable = [
        'etablissement_id', 'nom', 'description', 'prix_base',
        'capacite_max', 'nb_lits', 'superficie_m2',
    ];

    protected $casts = [
        'prix_base' => 'decimal:2',
        'capacite_max' => 'integer',
        'nb_lits' => 'integer',
    ];

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function chambres()
    {
        return $this->hasMany(Chambre::class);
    }

    public function tarifs()
    {
        return $this->hasMany(Tarif::class);
    }
}
