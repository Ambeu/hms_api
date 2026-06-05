<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Client extends Authenticatable implements JWTSubject
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'etablissement_id', 'nom', 'prenom', 'email', 'password',
        'telephone', 'nationalite', 'type_document', 'numero_document',
        'date_naissance', 'segment', 'points_fidelite', 'preferences',
    ];

    protected $hidden = ['password', 'remember_token'];

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return ['guard' => 'client', 'etablissement_id' => $this->etablissement_id];
    }

    protected $casts = [
        'date_naissance' => 'date',
        'preferences' => 'array',
        'points_fidelite' => 'integer',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
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

    public function objetOublies()
    {
        return $this->hasMany(ObjetOublie::class);
    }

    public function getNomCompletAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }
}
