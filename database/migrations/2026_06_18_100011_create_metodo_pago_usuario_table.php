<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metodo_pago_usuario', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->string('stripe_customer_id', 100)->nullable();
            $table->string('stripe_pm_id', 100)->nullable(); // PaymentMethod ID
            $table->string('brand', 30)->nullable();         // visa, mastercard, etc.
            $table->char('last4', 4)->nullable();
            $table->boolean('es_principal')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('usuario')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metodo_pago_usuario');
    }
};
