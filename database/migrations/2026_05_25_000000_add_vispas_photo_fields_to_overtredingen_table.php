<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtredingen', function (Blueprint $table) {
            if (!Schema::hasColumn('overtredingen', 'vispas_foto_path')) {
                $table->string('vispas_foto_path')->nullable()->after('vispasnummer');
            }

            if (!Schema::hasColumn('overtredingen', 'vispas_scan_confidence')) {
                $table->unsignedTinyInteger('vispas_scan_confidence')->nullable()->after('vispas_foto_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('overtredingen', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('overtredingen', 'vispas_scan_confidence') ? 'vispas_scan_confidence' : null,
                Schema::hasColumn('overtredingen', 'vispas_foto_path') ? 'vispas_foto_path' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
