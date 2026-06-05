<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeEtablissement extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom', 'description',
    ];

    public function etablissements()
    {
        return $this->hasMany(Etablissement::class);
    }
}
