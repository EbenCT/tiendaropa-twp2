<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Catalogo extends Model
{
    protected $table = 'catalogo';
    public $timestamps = false;
    protected $fillable = ['nombre', 'tipo', 'descripcion', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'catalogo_producto', 'catalogo_id', 'producto_id');
    }
}
