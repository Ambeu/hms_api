<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LigneCommande extends Model
{
    use HasFactory;

    protected $fillable = [
        'commande_id', 'article_id', 'quantite', 'prix_unitaire', 'notes', 'statut',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_unitaire' => 'decimal:2',
    ];

    public function commande()
    {
        return $this->belongsTo(CommandeRestaurant::class, 'commande_id');
    }

    public function article()
    {
        return $this->belongsTo(ArticleMenu::class, 'article_id');
    }

    public function getMontantTotalAttribute(): float
    {
        return $this->quantite * $this->prix_unitaire;
    }
}
