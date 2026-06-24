<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pago';
    protected $fillable = [
        'venta_id', 'modalidad', 'monto_total', 'num_cuotas',
        'fecha_pago', 'activo',
        'stripe_payment_intent_id', 'stripe_status', 'metodo',
    ];
    protected $casts = ['monto_total' => 'decimal:2', 'activo' => 'boolean', 'fecha_pago' => 'date'];

    public function venta()  { return $this->belongsTo(Venta::class, 'venta_id'); }
    public function cuotas() { return $this->hasMany(Cuota::class, 'pago_id'); }
}
