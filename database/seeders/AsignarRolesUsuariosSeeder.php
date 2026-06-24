<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AsignarRolesUsuariosSeeder extends Seeder
{
    /**
     * Asigna rol_nuevo a los usuarios existentes en la tabla 'usuario'.
     * Mapea el campo 'rol' (varchar libre del proyecto Java) al nuevo sistema.
     */
    public function run(): void
    {
        // Mapa de rol Java → rol nuevo normalizado
        $mapa = [
            'ADMIN'       => 'admin',
            'ADMINISTRADOR'=> 'admin',
            'PROPIETARIO' => 'propietario',
            'VENDEDOR'    => 'vendedor',
            'CLIENTE'     => 'cliente',
            'USUARIO'     => 'cliente',
        ];

        $usuarios = DB::table('usuario')->get();

        foreach ($usuarios as $u) {
            $rolOriginal = strtoupper(trim($u->rol ?? ''));
            $rolNuevo = $mapa[$rolOriginal] ?? 'cliente'; // default: cliente

            DB::table('usuario')
                ->where('id', $u->id)
                ->update(['rol_nuevo' => $rolNuevo]);
        }

        $this->command->info('Roles asignados a ' . count($usuarios) . ' usuarios.');
    }
}
