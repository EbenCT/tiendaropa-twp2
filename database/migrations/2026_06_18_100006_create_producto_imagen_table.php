<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_imagen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->string('url', 500);
            $table->boolean('es_principal')->default(false);
            $table->unsignedTinyInteger('orden')->default(0);
            $table->timestamps();

            $table->foreign('producto_id')->references('id')->on('producto')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_imagen');
    }
};
