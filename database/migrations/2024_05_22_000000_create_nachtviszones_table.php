<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('nachtviszones')) {
            Schema::create('nachtviszones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('water_id')->constrained();
                $table->json('boundary');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nachtviszones');
    }
};