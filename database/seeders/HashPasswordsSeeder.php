<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HashPasswordsSeeder extends Seeder
{
    /**
     * Hashea las contraseñas de los usuarios heredados del proyecto Java
     * que están en texto plano (no comienzan con '$2y$').
     */
    public function run(): void
    {
        $usuarios = DB::table('usuario')->get();
        $count = 0;

        foreach ($usuarios as $u) {
            // Si la contraseña NO es un hash bcrypt, la hasheamos
            if (!str_starts_with($u->password, '$2y$') && !str_starts_with($u->password, '$2a$')) {
                DB::table('usuario')
                    ->where('id', $u->id)
                    ->update(['password' => Hash::make($u->password)]);
                $count++;
            }
        }

        $this->command->info("Contraseñas hasheadas: {$count} de " . count($usuarios) . " usuarios.");
    }
}
