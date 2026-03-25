<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * database/migrations/2026_03_24_000000_create_overlast_meldingen_table.php
 *
 * Creëert de `overlast_meldingen` tabel voor het publieke meldformulier.
 * Dit formulier is specifiek bedoeld voor meldingen over sportvisserij en dierenwelzijn.
 *
 * Bevat:
 * - Categorie van de melding (vissterfte, onjuist gedrag, etc.)
 * - Beschrijving en details van de melding
 * - Locatiegegevens (adres + optioneel kaartgegevens)
 * - Melder-informatie (optioneel + anoniem optie)
 * - Bijlagen (foto's)
 * - Status tracking (nieuw, in behandeling, afgehandeld, afgewezen)
 * - Interne notities voor beheerders
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('overlast_meldingen', function (Blueprint $table) {
            $table->id();

            /**
             * CATEGORIE VAN DE MELDING
             */
            $table->enum('categorie', [
                'vissterfte',
                'onjuist_gedrag_vissers',
                'dierenmishandeling',
                'illegale_visserij',
                'vervuiling',
                'overig',
            ])->comment('Type melding (beperkt tot sportvisserij-gerelateerd)');

            /**
             * MELDING DETAILS
             */
            $table->text('beschrijving')->comment('Gedetailleerde beschrijving van de melding (verplicht)');
            $table->dateTime('melding_datum_tijd')->nullable()->comment('Datum en tijdstip van het incident');
            $table->integer('aantal_vissen')->nullable()->comment('Optioneel: aantal betrokken vissen');
            $table->string('ernst_situatie')->nullable()->comment('Optioneel: ernst beoordeling (laag/midden/hoog)');

            /**
             * LOCATIEGEGEVENS
             */
            $table->string('locatie_adres')->nullable()->comment('Adres of nabij adres van het incident');
            $table->json('locatie_details')->nullable()->comment('JSON met kaartselectie (lat, lng, address, etc.)');

            /**
             * BIJLAGEN
             */
            $table->json('fotos')->nullable()->comment('JSON array met bestandsnamen van geüploade foto\'s');

            /**
             * MELDER INFORMATIE
             */
            $table->string('melder_naam')->nullable()->comment('Naam van de melder (optioneel)');
            $table->string('melder_email')->nullable()->comment('Email van de melder (optioneel)');
            $table->string('melder_telefoon')->nullable()->comment('Telefoon van de melder (optioneel)');
            $table->boolean('melder_anoniem')->default(false)->comment('Melder wil anoniem blijven');

            /**
             * VALIDATIE EN MISBRUIKPREVENTIE
             */
            $table->boolean('categorie_scope_geldig')->default(false)->comment('Melder bevestigt dat melding binnen scope valt');
            $table->string('captcha_token')->nullable()->comment('CAPTCHA verificatietoken');

            /**
             * STATUS EN BEHEER
             */
            $table->enum('status', [
                'nieuw',
                'in_behandeling',
                'afgehandeld',
                'afgewezen',
            ])->default('nieuw')->comment('Status van de melding');

            $table->text('interne_notities')->nullable()->comment('Notities voor intern beheer (niet zichtbaar voor melder)');
            $table->text('afgewezen_reden')->nullable()->comment('Reden van afwijzing (waar van toepassing)');

            /**
             * BEHEERDER METADATA
             */
            $table->foreignId('verwerkt_door')->nullable()->constrained('users')->nullOnDelete()->comment('Beheerder die de melding verwerkt heeft');
            $table->dateTime('verwerkt_op')->nullable()->comment('Moment van verwerking/afwijzing');

            /**
             * STANDAARD TIMESTAMPS
             */
            $table->timestamps();
            $table->softDeletes();

            /**
             * INDICES VOOR PRESTATIES
             */
            $table->index(['categorie', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('melder_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overlast_meldingen');
    }
};
