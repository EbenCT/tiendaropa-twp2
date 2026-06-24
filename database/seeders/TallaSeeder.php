<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TallaSeeder extends Seeder
{
    public function run(): void
    {
        $tallas = [
            // Ropa adulto
            ['codigo' => 'XS',  'descripcion' => 'Extra Small', 'tipo' => 'ropa_adulto'],
            ['codigo' => 'S',   'descripcion' => 'Small',       'tipo' => 'ropa_adulto'],
            ['codigo' => 'M',   'descripcion' => 'Medium',      'tipo' => 'ropa_adulto'],
            ['codigo' => 'L',   'descripcion' => 'Large',       'tipo' => 'ropa_adulto'],
            ['codigo' => 'XL',  'descripcion' => 'Extra Large', 'tipo' => 'ropa_adulto'],
            ['codigo' => 'XXL', 'descripcion' => 'Double XL',  'tipo' => 'ropa_adulto'],
            // Ropa niño
            ['codigo' => '2',  'descripcion' => 'Talla 2 (2 años)',  'tipo' => 'ropa_nino'],
            ['codigo' => '4',  'descripcion' => 'Talla 4 (4 años)',  'tipo' => 'ropa_nino'],
            ['codigo' => '6',  'descripcion' => 'Talla 6 (6 años)',  'tipo' => 'ropa_nino'],
            ['codigo' => '8',  'descripcion' => 'Talla 8 (8 años)',  'tipo' => 'ropa_nino'],
            ['codigo' => '10', 'descripcion' => 'Talla 10 (10 años)', 'tipo' => 'ropa_nino'],
            ['codigo' => '12', 'descripcion' => 'Talla 12 (12 años)', 'tipo' => 'ropa_nino'],
            ['codigo' => '14', 'descripcion' => 'Talla 14 (14 años)', 'tipo' => 'ropa_nino'],
            // Calzado
            ['codigo' => '36', 'descripcion' => 'Calzado talla 36', 'tipo' => 'calzado'],
            ['codigo' => '37', 'descripcion' => 'Calzado talla 37', 'tipo' => 'calzado'],
            ['codigo' => '38', 'descripcion' => 'Calzado talla 38', 'tipo' => 'calzado'],
            ['codigo' => '39', 'descripcion' => 'Calzado talla 39', 'tipo' => 'calzado'],
            ['codigo' => '40', 'descripcion' => 'Calzado talla 40', 'tipo' => 'calzado'],
            ['codigo' => '41', 'descripcion' => 'Calzado talla 41', 'tipo' => 'calzado'],
            ['codigo' => '42', 'descripcion' => 'Calzado talla 42', 'tipo' => 'calzado'],
            ['codigo' => '43', 'descripcion' => 'Calzado talla 43', 'tipo' => 'calzado'],
        ];

        foreach ($tallas as $t) {
            DB::table('talla')->insertOrIgnore(
                array_merge($t, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
