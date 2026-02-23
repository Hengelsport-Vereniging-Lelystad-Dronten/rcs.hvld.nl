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
        if (!Schema::hasColumn('waters', 'is_verboden')) {
            Schema::table('waters', function (Blueprint $table) {
                // Boolean flag om aan te geven of het water verboden is
                $table->boolean('is_verboden')->default(false)->after('boundary');
                
                // Optionele koppeling naar een standaard overtredingstype (bijv. code 10)
                // Dit wordt voorgeselecteerd bij het registreren van een overtreding op dit water.
                $table->foreignId('default_overtreding_type_id')->nullable()->after('is_verboden')->constrained('overtreding_types')->nullOnDelete();
            });

        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waters', function (Blueprint $table) {
            $table->dropForeign(['default_overtreding_type_id']);
            $table->dropColumn(['default_overtreding_type_id', 'is_verboden']);
        });
    }
};