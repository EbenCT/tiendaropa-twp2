<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'venta';
    public $timestamps = false;
    protected $fillable = ['pedido_id', 'usuario_id', 'fecha', 'total', 'activo'];
    protected $casts = ['total' => 'decimal:2', 'activo' => 'boolean', 'fecha' => 'datetime'];

    public function pedido()  { return $this->belongsTo(Pedido::class, 'pedido_id'); }
    public function usuario() { return $this->belongsTo(User::class, 'usuario_id'); }
    public function pagos()   { return $this->hasMany(Pago::class, 'venta_id'); }
}
