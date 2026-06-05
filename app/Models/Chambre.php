<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chambre extends Model
{
    use HasFactory;

    protected $fillable = [
        'etablissement_id', 'type_chambre_id', 'etage_id', 'numero',
        'statut', 'vue', 'hors_service', 'equipements', 'description',
    ];

    protected $casts = [
        'hors_service' => 'boolean',
        'equipements' => 'array',
    ];

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function typeChambre()
    {
        return $this->belongsTo(TypeChambre::class);
    }

    public function etage()
    {
        return $this->belongsTo(Etage::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function tacheMenages()
    {
        return $this->hasMany(TacheMenage::class);
    }

    public function objetOublies()
    {
        return $this->hasMany(ObjetOublie::class);
    }

    public function images()
    {
        return $this->hasMany(ChambreImage::class)->orderBy('ordre');
    }

    public function imagePrincipale()
    {
        return $this->hasOne(ChambreImage::class)->where('principale', true)->orderBy('ordre');
    }
}
