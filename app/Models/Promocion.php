<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promocion extends Model
{
    protected $table = 'promocion';
    protected $fillable = ['nombre', 'descripcion', 'descuento', 'fecha_inicio', 'fecha_fin', 'activo'];
    protected $casts = ['descuento' => 'decimal:2', 'activo' => 'boolean', 'fecha_inicio' => 'date', 'fecha_fin' => 'date'];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_promocion', 'promocion_id', 'producto_id');
    }

    public function isActiva(): bool
    {
        $hoy = now()->toDateString();
        return $this->activo && $this->fecha_inicio <= $hoy && $this->fecha_fin >= $hoy;
    }
}
