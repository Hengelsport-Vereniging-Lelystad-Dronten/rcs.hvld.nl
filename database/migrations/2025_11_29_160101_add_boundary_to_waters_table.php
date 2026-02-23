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
        Schema::table('waters', function (Blueprint $table) {
            if (!Schema::hasColumn('waters', 'boundary')) {
                $table->json('boundary')->nullable()->after('beschrijving');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waters', function (Blueprint $table) {
            $table->dropColumn('boundary');
        });
    }
};