<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'etablissement_id', 'categorie_id', 'nom', 'description', 'prix',
        'allergenes', 'disponible', 'image_url',
    ];

    protected $casts = [
        'prix' => 'decimal:2',
        'allergenes' => 'array',
        'disponible' => 'boolean',
    ];

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menu_article', 'article_id', 'menu_id')->withTimestamps();
    }

    public function categorie()
    {
        return $this->belongsTo(CategorieMenu::class, 'categorie_id');
    }

    public function ligneCommandes()
    {
        return $this->hasMany(LigneCommande::class, 'article_id');
    }

    public function stock()
    {
        return $this->hasOne(StockArticle::class, 'article_id');
    }
}
