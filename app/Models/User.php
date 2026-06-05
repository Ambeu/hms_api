<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'current_etablissement_id', 'name', 'nom', 'prenom', 'phone',
        'email', 'password', 'role', 'actif', 'derniere_connexion',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'derniere_connexion' => 'datetime',
        'actif' => 'boolean',
    ];

    // ──────────────────────────────────────────
    // JWT
    // ──────────────────────────────────────────

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'current_etablissement_id' => $this->current_etablissement_id,
            'role' => $this->role,
        ];
    }

    // ──────────────────────────────────────────
    // Relations
    // ──────────────────────────────────────────

    /** Établissements auxquels cet utilisateur a accès */
    public function etablissements()
    {
        return $this->belongsToMany(Etablissement::class, 'etablissement_user')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /** Établissement actif (celui dans lequel il travaille en ce moment) */
    public function currentEtablissement()
    {
        return $this->belongsTo(Etablissement::class, 'current_etablissement_id');
    }

    public function checkinCheckouts()
    {
        return $this->hasMany(CheckinCheckout::class, 'utilisateur_id');
    }

    public function tacheMenages()
    {
        return $this->hasMany(TacheMenage::class, 'agent_id');
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    /** Changer d'établissement actif (vérifie l'accès avant de switcher) */
    public function switchEtablissement(int $etablissementId): bool
    {
        if (!$this->etablissements()->where('etablissement_id', $etablissementId)->exists()) {
            return false;
        }

        $this->update(['current_etablissement_id' => $etablissementId]);
        return true;
    }

    /** Vérifie si l'utilisateur a accès à un établissement donné */
    public function hasAccessTo(int $etablissementId): bool
    {
        return $this->etablissements()->where('etablissement_id', $etablissementId)->exists();
    }

    public function getNomCompletAttribute(): string
    {
        return trim("{$this->prenom} {$this->nom}") ?: $this->name;
    }
}
