<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pago', function (Blueprint $table) {
            $table->string('stripe_payment_intent_id', 255)->nullable()->after('fecha_pago');
            $table->string('stripe_status', 50)->nullable()->after('stripe_payment_intent_id');
            $table->string('metodo', 30)->nullable()->after('stripe_status');
        });
    }

    public function down(): void
    {
        Schema::table('pago', function (Blueprint $table) {
            $table->dropColumn(['stripe_payment_intent_id', 'stripe_status', 'metodo']);
        });
    }
};
