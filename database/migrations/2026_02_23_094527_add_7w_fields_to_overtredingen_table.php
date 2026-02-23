<?php

/**
 * database/migrations/2026_02_23_094527_add_7w_fields_to_overtredingen_table.php
 *
 * Past de `overtredingen` tabel aan om gestructureerde dataopslag
 * volgens de 7 W's methodiek (zonder 'Wie') mogelijk te maken.
 * Verwijdert tevens het 'vispasnummer' veld i.v.m. privacy.
 */

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
            // Maak de migratie idempotent door per kolom te controleren of deze al bestaat.
            // Dit voorkomt fouten als de migratie opnieuw wordt uitgevoerd na een eerdere mislukking.
            if (!Schema::hasColumn('overtredingen', 'locatie_details')) {
                $table->json('locatie_details')->nullable()->after('overtreding_type_id')->comment('WAAR: Locatie details (JSON: lat/lon, zone, water)');
            }
            if (!Schema::hasColumn('overtredingen', 'geconstateerd_op')) {
                $table->timestamp('geconstateerd_op')->nullable()->after('locatie_details')->comment('WANNEER: Datum en tijdstip van constatering');
            }
            if (!Schema::hasColumn('overtredingen', 'constatering_wijze')) {
                $table->string('constatering_wijze')->nullable()->after('geconstateerd_op')->comment('HOE: Wijze van constatering (Visueel, Melding, etc.)');
            }
            if (!Schema::hasColumn('overtredingen', 'aanleiding')) {
                $table->string('aanleiding')->nullable()->after('constatering_wijze')->comment('WAAROM: Aanleiding of context');
            }
            if (!Schema::hasColumn('overtredingen', 'middel')) {
                $table->string('middel')->nullable()->after('aanleiding')->comment('WAARMEE: Middel, voertuig of object');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtredingen', function (Blueprint $table) {
            // Controleer of de kolommen bestaan voordat ze worden verwijderd om fouten te voorkomen.
            $columns = ['locatie_details', 'geconstateerd_op', 'constatering_wijze', 'aanleiding', 'middel'];
            $table->dropColumn(array_filter($columns, fn($c) => Schema::hasColumn('overtredingen', $c)));
        });
    }
};