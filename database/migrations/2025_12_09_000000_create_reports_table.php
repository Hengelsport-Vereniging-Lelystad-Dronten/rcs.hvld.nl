<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * database/migrations/*_create_reports_table.php
 *
 * Creëert de `reports` tabel voor opslag van gegenereerde periodieke rapporten.
 * Bevat metadata over de rapportageperiode en een JSON blob met samengevatte statistieken.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            // Type rapport: 'weekly', 'monthly', 'quarterly', 'custom'
            $table->enum('report_type', ['weekly', 'monthly', 'quarterly', 'custom'])
                ->default('weekly')
                ->comment('Type van periodieke rapportage');

            // Rapportageperiode: start en eind
            $table->dateTime('period_start')->comment('Start van rapportageperiode');
            $table->dateTime('period_end')->comment('Eind van rapportageperiode');

            // JSON blob met samengevatte data (aantal overtredingen, top types, etc.)
            $table->json('data_summary')->nullable()->comment('Samengevatte statistieken en KPI\'s in JSON formaat');

            // Moment van generering
            $table->dateTime('generated_at')->nullable()->comment('Moment waarop rapport is gegenereerd');

            // Standaard Laravel timestamps
            $table->timestamps();

            // Index voor snelle opzoeking op periode en type
            $table->index(['report_type', 'period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
