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
        Schema::create('overtredingen', function (Blueprint $table) {
            $table->id();
            // RELATIE NAAR DE HOOFD CONTROLE RONDE
            $table->foreignId('controle_ronde_id')->constrained()->onDelete('cascade');
            
            // Foreign Key naar de OvertredingTypes tabel voor de code
            $table->foreignId('overtreding_type_id')->constrained()->comment('Verwijzing naar het type overtreding');

            $table->string('vispasnummer')->nullable()->comment('Optioneel vispasnummer van de overtreder');
            $table->string('genomen_maatregel')->comment('Bijv. Waarschuwing, Rapportage, Inbeslagname');
            $table->text('details')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtredingen');
    }
};
