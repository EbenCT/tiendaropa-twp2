<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            // Columnas requeridas por Laravel Auth
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->string('remember_token', 100)->nullable()->after('password');
            // FK al nuevo sistema de roles
            $table->string('rol_nuevo', 20)->nullable()->after('rol')
                  ->comment('admin|propietario|vendedor|cliente — reemplaza el campo rol varchar libre');
        });
    }

    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn(['email_verified_at', 'remember_token', 'rol_nuevo']);
        });
    }
};
