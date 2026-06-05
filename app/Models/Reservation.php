<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'etablissement_id', 'client_id', 'chambre_id', 'tarif_id',
        'canal_source', 'statut', 'unite', 'date_arrivee', 'date_depart',
        'nb_adultes', 'nb_enfants', 'prix_total', 'code_confirmation', 'notes_speciales',
    ];

    protected $casts = [
        'date_arrivee' => 'datetime',
        'date_depart'  => 'datetime',
        'prix_total' => 'decimal:2',
        'nb_adultes' => 'integer',
        'nb_enfants' => 'integer',
    ];

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function chambre()
    {
        return $this->belongsTo(Chambre::class);
    }

    public function tarif()
    {
        return $this->belongsTo(Tarif::class);
    }

    public function checkinCheckouts()
    {
        return $this->hasMany(CheckinCheckout::class);
    }

    public function factures()
    {
        return $this->hasMany(Facture::class);
    }

    public function commandeRestaurants()
    {
        return $this->hasMany(CommandeRestaurant::class);
    }

    public function transactionPoints()
    {
        return $this->hasMany(TransactionPoints::class);
    }

    public function avisClients()
    {
        return $this->hasMany(AvisClient::class);
    }

    /** Nombre d'unités (heures, nuits, jours…) selon le tarif appliqué */
    public function getNbUnitesAttribute(): int
    {
        return match ($this->unite) {
            'heure'   => $this->date_arrivee->diffInHours($this->date_depart),
            'jour'    => $this->date_arrivee->diffInDays($this->date_depart),
            'semaine' => (int) ceil($this->date_arrivee->diffInDays($this->date_depart) / 7),
            default   => $this->date_arrivee->diffInDays($this->date_depart), // nuit
        };
    }

    /** Libellé lisible de la durée */
    public function getDureeLibelleAttribute(): string
    {
        $n = $this->nb_unites;
        return match ($this->unite) {
            'heure'   => "{$n} heure" . ($n > 1 ? 's' : ''),
            'jour'    => "{$n} jour"  . ($n > 1 ? 's' : ''),
            'semaine' => "{$n} semaine" . ($n > 1 ? 's' : ''),
            default   => "{$n} nuit"  . ($n > 1 ? 's' : ''),
        };
    }
}
