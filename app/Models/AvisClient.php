<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvisClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'etablissement_id', 'client_id', 'reservation_id',
        'note_globale', 'note_chambre', 'note_service', 'note_restauration', 'commentaire',
    ];

    protected $casts = [
        'note_globale' => 'integer',
        'note_chambre' => 'integer',
        'note_service' => 'integer',
        'note_restauration' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
