<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $table = 'menu_item';

    protected $fillable = [
        'label', 'route_name', 'url', 'icon',
        'role_nivel_minimo', 'parent_id', 'orden', 'activo',
    ];

    protected $casts = [
        'activo'            => 'boolean',
        'role_nivel_minimo' => 'integer',
        'orden'             => 'integer',
    ];

    public function children()
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('orden');
    }

    public function parent()
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }
}
