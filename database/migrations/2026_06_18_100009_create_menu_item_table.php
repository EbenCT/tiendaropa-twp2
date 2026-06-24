<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item', function (Blueprint $table) {
            $table->id();
            $table->string('label', 80);
            $table->string('route_name', 100)->nullable();  // nombre de ruta Laravel
            $table->string('url', 200)->nullable();         // URL directa (alternativa a route_name)
            $table->string('icon', 60)->nullable();         // nombre ícono (heroicons, etc.)
            $table->unsignedTinyInteger('role_nivel_minimo')->default(0); // 0=público, 1=cliente, 2=vendedor...
            $table->unsignedBigInteger('parent_id')->nullable(); // submenú
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('menu_item')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item');
    }
};
