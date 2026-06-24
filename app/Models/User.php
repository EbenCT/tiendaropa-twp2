<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuario';

    protected $fillable = [
        'ci', 'nombre', 'apellido', 'email',
        'telefono', 'password', 'rol', 'rol_nuevo', 'activo',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'activo'            => 'boolean',
    ];

    // ── Helpers de rol ──────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return ($this->rol_nuevo ?? $this->rol) === 'admin';
    }

    public function isPropietario(): bool
    {
        return in_array($this->rol_nuevo ?? $this->rol, ['admin', 'propietario']);
    }

    public function isVendedor(): bool
    {
        return in_array($this->rol_nuevo ?? $this->rol, ['admin', 'propietario', 'vendedor']);
    }

    public function isCliente(): bool
    {
        return ($this->rol_nuevo ?? $this->rol) === 'cliente';
    }

    public function getNivelRolAttribute(): int
    {
        return match ($this->rol_nuevo ?? $this->rol) {
            'admin'       => 4,
            'propietario' => 3,
            'vendedor'    => 2,
            'cliente'     => 1,
            default       => 0,
        };
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    // ── Relaciones ──────────────────────────────────────────────────

    public function carritoItems()
    {
        return $this->hasMany(CarritoItem::class, 'usuario_id');
    }

    public function favoritos()
    {
        return $this->hasMany(Favorito::class, 'usuario_id');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'usuario_id');
    }

    public function metodosPago()
    {
        return $this->hasMany(MetodoPagoUsuario::class, 'usuario_id');
    }
}
