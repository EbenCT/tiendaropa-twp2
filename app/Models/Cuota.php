<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuota extends Model
{
    protected $table = 'cuota';
    public $timestamps = false;
    protected $fillable = [
        'pago_id', 'num_cuota', 'monto', 'fecha_vencimiento', 'estado', 'fecha_pago_real',
        'pagofacil_transaction_id', 'pagofacil_status', 'pagofacil_qr_base64', 'pagofacil_expira_en',
    ];
    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'fecha_pago_real' => 'date',
        'pagofacil_expira_en' => 'datetime',
    ];

    public function pago() { return $this->belongsTo(Pago::class, 'pago_id'); }
}
