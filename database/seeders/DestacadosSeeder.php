<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DestacadosSeeder extends Seeder
{
    /**
     * Marca algunos productos activos como destacados y como nueva colección
     * para que las secciones del Home se visualicen correctamente.
     */
    public function run(): void
    {
        $productos = DB::table('producto')
            ->where('activo', true)
            ->orderBy('id')
            ->limit(20)
            ->pluck('id')
            ->toArray();

        if (empty($productos)) {
            $this->command->warn('No hay productos activos para marcar como destacados.');
            return;
        }

        // Marcar los primeros 4 como destacados
        $destacadoIds = array_slice($productos, 0, min(4, count($productos)));
        DB::table('producto')
            ->whereIn('id', $destacadoIds)
            ->update(['destacado' => true]);

        // Marcar los siguientes 4 (o los mismos si hay pocos) como nueva colección
        $nuevaColIds = array_slice($productos, min(4, count($productos) - 1), min(4, count($productos)));
        if (empty($nuevaColIds)) {
            $nuevaColIds = $destacadoIds;
        }
        DB::table('producto')
            ->whereIn('id', $nuevaColIds)
            ->update(['es_nueva_coleccion' => true]);

        $this->command->info('Destacados: ' . count($destacadoIds) . ' productos. Nueva colección: ' . count($nuevaColIds) . ' productos.');
    }
}
