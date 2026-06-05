<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommandeRestaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'etablissement_id', 'client_id', 'reservation_id', 'type_commande',
        'statut', 'numero_table', 'numero_chambre', 'montant_total', 'servie_at',
    ];

    protected $casts = [
        'montant_total' => 'decimal:2',
        'numero_table' => 'integer',
        'servie_at' => 'datetime',
    ];

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'commande_menu', 'commande_id', 'menu_id')->withTimestamps();
    }

    public function facture()
    {
        return $this->hasOne(Facture::class, 'commande_restaurant_id');
    }

    public function ligneCommandes()
    {
        return $this->hasMany(LigneCommande::class, 'commande_id');
    }
}
