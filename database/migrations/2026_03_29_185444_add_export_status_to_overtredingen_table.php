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
            $table->enum('export_status', ['niet_exporteren', 'wel_exporteren', 'geexporteerd'])
                  ->default('wel_exporteren')
                  ->after('exported_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtredingen', function (Blueprint $table) {
            $table->dropColumn('export_status');
        });
    }
};
