<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodoPagoUsuario extends Model
{
    use HasFactory;

    protected $table = 'metodo_pago_usuario';

    protected $fillable = [
        'usuario_id', 'stripe_customer_id', 'stripe_pm_id', 'brand', 'last4', 'es_principal', 'activo',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'activo'       => 'boolean',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
