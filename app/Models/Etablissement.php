<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Etablissement extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_etablissement_id', 'devise_id',
        'nom', 'slug', 'adresse', 'telephone', 'whatsapp', 'email', 'site_web',
        'nombre_etoiles', 'nb_etages', 'image_couverture', 'logo_url',
        'latitude', 'longitude',
        'numero_registre', 'numero_fiscal', 'date_ouverture', 'taux_tva',
    ];

    protected $casts = [
        'nombre_etoiles' => 'integer',
        'nb_etages' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'date_ouverture' => 'date',
        'taux_tva' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $etablissement) {
            if (empty($etablissement->slug)) {
                $etablissement->slug = self::genererSlugUnique($etablissement->nom);
            }
        });
    }

    public static function genererSlugUnique(string $nom, ?int $ignoreId = null): string
    {
        $base = Str::slug($nom);
        $slug = $base;

        $query = static::where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        while ($query->clone()->exists()) {
            $slug  = $base . '-' . rand(100, 999);
            $query = static::where('slug', $slug);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
        }

        return $slug;
    }

    // ──────────────────────────────────────────
    // Relations
    // ──────────────────────────────────────────

    public function typeEtablissement()
    {
        return $this->belongsTo(TypeEtablissement::class);
    }

    public function devise()
    {
        return $this->belongsTo(Devise::class);
    }

    /** Tous les utilisateurs ayant accès à cet établissement */
    public function utilisateurs()
    {
        return $this->belongsToMany(User::class, 'etablissement_user')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /** Utilisateurs actuellement connectés à cet établissement */
    public function utilisateursCourants()
    {
        return $this->hasMany(User::class, 'current_etablissement_id');
    }

    public function etages()
    {
        return $this->hasMany(Etage::class);
    }

    public function typeChambres()
    {
        return $this->hasMany(TypeChambre::class);
    }

    public function chambres()
    {
        return $this->hasMany(Chambre::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

    public function commandeRestaurants()
    {
        return $this->hasMany(CommandeRestaurant::class);
    }

    public function categoriesMenu()
    {
        return $this->hasMany(CategorieMenu::class);
    }
}
