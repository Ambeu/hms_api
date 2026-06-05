<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'etablissement_id', 'nom', 'description', 'type_repas', 'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function articleMenus()
    {
        return $this->belongsToMany(ArticleMenu::class, 'menu_article', 'menu_id', 'article_id')->withTimestamps();
    }

    public function images()
    {
        return $this->hasMany(MenuImage::class)->orderBy('ordre');
    }

    public function imagePrincipale()
    {
        return $this->hasOne(MenuImage::class)->where('principale', true)->orderBy('ordre');
    }
}
