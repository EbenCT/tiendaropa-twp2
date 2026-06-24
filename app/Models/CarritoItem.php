<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarritoItem extends Model
{
    protected $table = 'carrito_item';
    protected $fillable = ['usuario_id', 'producto_id', 'talla_id', 'cantidad'];

    public function producto() { return $this->belongsTo(Producto::class, 'producto_id'); }
    public function talla()    { return $this->belongsTo(Talla::class, 'talla_id'); }
    public function usuario()  { return $this->belongsTo(User::class, 'usuario_id'); }

    public function getSubtotalAttribute(): float
    {
        return $this->cantidad * ($this->producto?->precio_unitario ?? 0);
    }
}
