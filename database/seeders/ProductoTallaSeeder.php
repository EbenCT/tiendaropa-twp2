<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductoTallaSeeder extends Seeder
{
    /**
     * Migra la talla única heredada de la columna 'producto.talla' (proyecto Java)
     * a la tabla pivot 'producto_talla', usando el stock actual del producto.
     * Sin esto, el selector de tallas en el detalle de producto nunca aparece.
     */
    public function run(): void
    {
        $categoriasNino = DB::table('categoria')
            ->where('nombre', 'ilike', '%nino%')
            ->orWhere('nombre', 'ilike', '%niño%')
            ->pluck('id')
            ->toArray();

        $productos = DB::table('producto')->whereNotNull('talla')->get();
        $asignados = 0;

        foreach ($productos as $producto) {
            $codigo = trim($producto->talla);
            if ($codigo === '') {
                continue;
            }

            $tipo = in_array($producto->categoria_id, $categoriasNino) ? 'ropa_nino' : 'ropa_adulto';
            if (!in_array($tipo, ['ropa_nino']) && is_numeric($codigo)) {
                $tipo = 'pantalon';
            }

            $talla = DB::table('talla')->where('codigo', $codigo)->where('tipo', $tipo)->first()
                ?? DB::table('talla')->where('codigo', $codigo)->first();

            if (!$talla) {
                continue;
            }

            DB::table('producto_talla')->updateOrInsert(
                ['producto_id' => $producto->id, 'talla_id' => $talla->id],
                ['stock' => $producto->stock_actual, 'created_at' => now(), 'updated_at' => now()]
            );
            $asignados++;
        }

        $this->command->info("Tallas asignadas a {$asignados} de " . count($productos) . ' productos.');
    }
}
