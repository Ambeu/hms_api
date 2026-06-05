<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorieMenu extends Model
{
    use HasFactory;

    protected $table = 'categories_menu';

    protected $fillable = [
        'etablissement_id', 'nom', 'description', 'icone', 'ordre', 'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'ordre' => 'integer',
    ];

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function articles()
    {
        return $this->hasMany(ArticleMenu::class, 'categorie_id');
    }
}
