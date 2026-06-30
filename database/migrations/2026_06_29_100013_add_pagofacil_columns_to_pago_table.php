<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pago', function (Blueprint $table) {
            $table->string('gateway', 20)->nullable()->after('metodo');
            $table->string('pagofacil_transaction_id', 100)->nullable()->after('gateway');
            $table->string('pagofacil_status', 60)->nullable()->after('pagofacil_transaction_id');
            $table->text('pagofacil_qr_base64')->nullable()->after('pagofacil_status');
            $table->timestamp('pagofacil_expira_en')->nullable()->after('pagofacil_qr_base64');
        });
    }

    public function down(): void
    {
        Schema::table('pago', function (Blueprint $table) {
            $table->dropColumn([
                'gateway',
                'pagofacil_transaction_id',
                'pagofacil_status',
                'pagofacil_qr_base64',
                'pagofacil_expira_en',
            ]);
        });
    }
};
