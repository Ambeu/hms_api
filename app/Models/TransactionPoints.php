<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPoints extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'reservation_id', 'type_operation', 'points',
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
