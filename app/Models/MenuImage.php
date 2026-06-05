<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuImage extends Model
{
    protected $fillable = ['menu_id', 'url', 'ordre', 'principale'];

    protected $casts = ['principale' => 'boolean', 'ordre' => 'integer'];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
