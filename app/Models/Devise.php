<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Devise extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'nom', 'symbole', 'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function etablissements()
    {
        return $this->hasMany(Etablissement::class);
    }
}
