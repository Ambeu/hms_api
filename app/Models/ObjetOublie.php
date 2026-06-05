<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObjetOublie extends Model
{
    use HasFactory;

    protected $fillable = [
        'etablissement_id', 'chambre_id', 'client_id',
        'numero_chambre', 'description', 'contact_restitution', 'statut', 'date_signalement',
    ];

    protected $casts = [
        'date_signalement' => 'datetime',
    ];

    public function chambre()
    {
        return $this->belongsTo(Chambre::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
