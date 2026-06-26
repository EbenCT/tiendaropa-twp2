<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $table = 'inventario';
    public $timestamps = false;
    protected $fillable = ['producto_id', 'usuario_id', 'tipo', 'cantidad', 'costo_unitario', 'tecnica', 'fecha', 'observacion'];
    protected $casts = ['costo_unitario' => 'decimal:2', 'fecha' => 'datetime'];

    public function producto() { return $this->belongsTo(Producto::class, 'producto_id'); }
    public function usuario()  { return $this->belongsTo(User::class, 'usuario_id'); }
}
