<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->upsert([
            ['slug' => 'admin',       'nombre' => 'Administrador', 'nivel' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'propietario', 'nombre' => 'Propietario',   'nivel' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'vendedor',    'nombre' => 'Vendedor',      'nivel' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['slug' => 'cliente',     'nombre' => 'Cliente',       'nivel' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], uniqueBy: ['slug'], update: ['nombre', 'nivel', 'updated_at']);
    }
}
