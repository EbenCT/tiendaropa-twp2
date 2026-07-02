<?php

namespace App\Services;

use App\Models\Bitacora;
use Illuminate\Support\Facades\Auth;

class BitacoraService
{
    public static function registrar(
        string $accion,
        string $modulo,
        string $descripcion,
        ?int $usuarioId = null
    ): void {
        try {
            Bitacora::create([
                'usuario_id'  => $usuarioId ?? Auth::id(),
                'accion'      => $accion,
                'modulo'      => $modulo,
                'descripcion' => $descripcion,
                'ip'          => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            // La bitácora nunca debe romper el flujo principal
        }
    }

    public static function login(string $email, ?int $usuarioId): void
    {
        self::registrar('LOGIN', 'auth', "Inicio de sesión: {$email}", $usuarioId);
    }

    public static function loginFallido(string $email): void
    {
        self::registrar('LOGIN_FALLIDO', 'auth', "Intento fallido de login: {$email}", null);
    }

    public static function logout(int $usuarioId, string $nombre): void
    {
        self::registrar('LOGOUT', 'auth', "Cierre de sesión: {$nombre}", $usuarioId);
    }

    public static function pago(int $usuarioId, string $descripcion): void
    {
        self::registrar('PAGO', 'pagos', $descripcion, $usuarioId);
    }

    public static function inventario(int $usuarioId, string $descripcion): void
    {
        self::registrar('INVENTARIO', 'inventario', $descripcion, $usuarioId);
    }

    public static function registro(int $usuarioId, string $nombre): void
    {
        self::registrar('REGISTRO', 'auth', "Nuevo usuario registrado: {$nombre}", $usuarioId);
    }
}
