<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->string('pref_tema', 20)->default('adultos')->after('rol_nuevo');
            $table->string('pref_modo', 10)->default('auto')->after('pref_tema');
            $table->decimal('pref_escala', 3, 1)->default(1.0)->after('pref_modo');
        });
    }

    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn(['pref_tema', 'pref_modo', 'pref_escala']);
        });
    }
};
