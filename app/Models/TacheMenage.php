<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TacheMenage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chambre_id', 'agent_id', 'type_tache', 'statut',
        'date_assignation', 'date_debut', 'date_fin', 'observations', 'checklist', 'lien_token',
    ];

    protected $casts = [
        'checklist' => 'array',
        'date_assignation' => 'datetime',
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
    ];

    public function chambre()
    {
        return $this->belongsTo(Chambre::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
