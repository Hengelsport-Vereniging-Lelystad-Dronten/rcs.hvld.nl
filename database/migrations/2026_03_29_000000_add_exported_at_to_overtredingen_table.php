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
        Schema::table('overtredingen', function (Blueprint $table) {
            $table->timestamp('exported_at')->nullable()->after('geannuleerd_op')->comment('Timestamp wanneer de overtreding is geëxporteerd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtredingen', function (Blueprint $table) {
            $table->dropColumn('exported_at');
        });
    }
};