<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_talla', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('talla_id');
            $table->integer('stock')->default(0);
            $table->timestamps();

            $table->unique(['producto_id', 'talla_id']);
            $table->foreign('producto_id')->references('id')->on('producto')->onDelete('cascade');
            $table->foreign('talla_id')->references('id')->on('talla')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_talla');
    }
};
