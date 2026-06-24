<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talla', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10);   // S, M, L, XL, 6, 8, 10, 36...
            $table->string('descripcion', 50)->nullable();
            $table->string('tipo', 20)->default('ropa_adulto'); // ropa_adulto | ropa_nino | calzado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talla');
    }
};
