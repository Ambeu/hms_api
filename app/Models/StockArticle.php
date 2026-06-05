<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id', 'quantite_disponible', 'seuil_alerte', 'mise_a_jour',
    ];

    protected $casts = [
        'quantite_disponible' => 'integer',
        'seuil_alerte' => 'integer',
        'mise_a_jour' => 'datetime',
    ];

    public function article()
    {
        return $this->belongsTo(ArticleMenu::class, 'article_id');
    }

    public function estEnRupture(): bool
    {
        return $this->quantite_disponible <= $this->seuil_alerte;
    }
}
