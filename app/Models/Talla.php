<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Talla extends Model
{
    protected $table = 'talla';
    protected $fillable = ['codigo', 'descripcion', 'tipo'];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_talla', 'talla_id', 'producto_id')
                    ->withPivot('stock')
                    ->withTimestamps();
    }
}
