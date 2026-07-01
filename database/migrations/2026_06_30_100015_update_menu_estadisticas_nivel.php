<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Estadísticas visible para propietario (nivel 3) y admin (nivel 4)
        DB::table('menu_item')
            ->where('route_name', 'admin.estadisticas')
            ->update(['role_nivel_minimo' => 3]);

        // Invalidar caché del menú para todos los niveles
        foreach ([0, 1, 2, 3, 4] as $nivel) {
            Cache::forget("menu_nivel_{$nivel}");
        }
    }

    public function down(): void
    {
        DB::table('menu_item')
            ->where('route_name', 'admin.estadisticas')
            ->update(['role_nivel_minimo' => 4]);

        foreach ([0, 1, 2, 3, 4] as $nivel) {
            Cache::forget("menu_nivel_{$nivel}");
        }
    }
};
