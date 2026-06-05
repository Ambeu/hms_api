<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id', 'commande_restaurant_id', 'client_id', 'numero_facture', 'type_facture',
        'statut', 'montant_ht', 'tva', 'montant_ttc', 'mode_paiement',
        'date_emission', 'date_paiement',
    ];

    protected $casts = [
        'montant_ht' => 'decimal:2',
        'tva' => 'decimal:2',
        'montant_ttc' => 'decimal:2',
        'date_emission' => 'datetime',
        'date_paiement' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function commandeRestaurant()
    {
        return $this->belongsTo(CommandeRestaurant::class, 'commande_restaurant_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function ligneFactures()
    {
        return $this->hasMany(LigneFacture::class);
    }
}
