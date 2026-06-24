<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrito_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('talla_id')->nullable();
            $table->unsignedInteger('cantidad')->default(1);
            $table->timestamps();

            $table->unique(['usuario_id', 'producto_id', 'talla_id']);
            $table->foreign('usuario_id')->references('id')->on('usuario')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('producto')->onDelete('cascade');
            $table->foreign('talla_id')->references('id')->on('talla')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrito_item');
    }
};
