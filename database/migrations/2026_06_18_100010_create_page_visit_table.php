<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_visit', function (Blueprint $table) {
            $table->id();
            $table->string('page_url', 500)->unique();
            $table->string('page_name', 200)->nullable();
            $table->unsignedBigInteger('visit_count')->default(0);
            $table->timestamp('last_visited_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visit');
    }
};
