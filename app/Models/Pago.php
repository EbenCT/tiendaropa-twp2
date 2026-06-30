<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pago';
    public $timestamps = false;
    protected $fillable = [
        'venta_id', 'modalidad', 'monto_total', 'num_cuotas',
        'fecha_pago', 'activo',
        'stripe_payment_intent_id', 'stripe_status', 'metodo',
        'gateway', 'pagofacil_transaction_id', 'pagofacil_status',
        'pagofacil_qr_base64', 'pagofacil_expira_en',
    ];
    protected $casts = [
        'monto_total' => 'decimal:2',
        'activo' => 'boolean',
        'fecha_pago' => 'date',
        'pagofacil_expira_en' => 'datetime',
    ];

    public function venta()  { return $this->belongsTo(Venta::class, 'venta_id'); }
    public function cuotas() { return $this->hasMany(Cuota::class, 'pago_id'); }
}
