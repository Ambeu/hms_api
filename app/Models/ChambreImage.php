<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChambreImage extends Model
{
    protected $fillable = ['chambre_id', 'url', 'ordre', 'principale'];

    protected $casts = ['principale' => 'boolean', 'ordre' => 'integer'];

    public function chambre()
    {
        return $this->belongsTo(Chambre::class);
    }
}
