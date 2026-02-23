<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtredingen', function (Blueprint $table) {
            $table->string('status')
                ->default('actief')
                ->after('vispas_ingenomen');
            $table->text('annulatie_reden')->nullable()->after('status');
            $table->foreignId('geannuleerd_door')
                ->nullable()
                ->after('annulatie_reden')
                ->constrained('users')
                ->nullOnDelete();
            $table->dateTime('geannuleerd_op')->nullable()->after('geannuleerd_door');
        });

        DB::table('overtredingen')
            ->whereNull('status')
            ->update(['status' => 'actief']);
    }

    public function down(): void
    {
        Schema::table('overtredingen', function (Blueprint $table) {
            $table->dropConstrainedForeignId('geannuleerd_door');
            $table->dropColumn([
                'status',
                'annulatie_reden',
                'geannuleerd_op',
            ]);
        });
    }
};
