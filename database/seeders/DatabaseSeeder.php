<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            TallaSeeder::class,
            MenuItemSeeder::class,
            AsignarRolesUsuariosSeeder::class,
            HashPasswordsSeeder::class,
            ProductoTallaSeeder::class,
            DestacadosSeeder::class,
        ]);
    }
}
