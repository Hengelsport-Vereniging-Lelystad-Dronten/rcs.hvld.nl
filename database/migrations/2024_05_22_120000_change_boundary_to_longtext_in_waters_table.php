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
            // Wijzig de boundary kolom naar LONGTEXT.
            // Dit zorgt ervoor dat complexe GeoJSON objecten (zoals MultiPolygons met veel punten)
            // niet worden afgekapt door de limiet van een standaard TEXT veld (64KB).
            $table->longText('boundary')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waters', function (Blueprint $table) {
            $table->text('boundary')->nullable()->change();
        });
    }
};