<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckinCheckout extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id', 'utilisateur_id', 'type', 'date_heure',
        'numero_cle', 'late_checkout', 'early_checkin', 'observations',
    ];

    protected $casts = [
        'date_heure' => 'datetime',
        'late_checkout' => 'boolean',
        'early_checkin' => 'boolean',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
