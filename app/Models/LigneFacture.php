<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LigneFacture extends Model
{
    use HasFactory;

    protected $fillable = [
        'facture_id', 'description', 'categorie', 'quantite',
        'prix_unitaire', 'montant_total', 'date_consommation',
    ];

    protected $casts = [
        'quantite' => 'decimal:2',
        'prix_unitaire' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'date_consommation' => 'datetime',
    ];

    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }
}
