<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedido';
    public $timestamps = false;
    protected $fillable = ['usuario_id', 'fecha', 'estado', 'direccion', 'telefono', 'referencia', 'total', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fecha' => 'datetime'];

    public function usuario()  { return $this->belongsTo(User::class, 'usuario_id'); }
    public function detalles() { return $this->hasMany(DetallePedido::class, 'pedido_id'); }
    public function venta()    { return $this->hasOne(Venta::class, 'pedido_id'); }
}
