<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarif extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_chambre_id', 'nom', 'prix', 'unite', 'duree_min',
        'date_debut', 'date_fin', 'type_tarif',
    ];

    protected $casts = [
        'prix'      => 'decimal:2',
        'duree_min' => 'integer',
        'date_debut' => 'date',
        'date_fin'   => 'date',
    ];

    public function estHoraire(): bool
    {
        return $this->unite === 'heure';
    }

    public function typeChambre()
    {
        return $this->belongsTo(TypeChambre::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
