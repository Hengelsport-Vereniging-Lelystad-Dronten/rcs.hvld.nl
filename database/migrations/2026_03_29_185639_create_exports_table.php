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
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('export_type')->default('overtredingen'); // overtredingen, reports, etc.
            $table->integer('record_count')->default(0);
            $table->json('filters')->nullable(); // opgeslagen filters voor reproduceerbaarheid
            $table->json('selected_records')->nullable(); // welke records zijn geëxporteerd
            $table->unsignedBigInteger('created_by');
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['export_type', 'created_at']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
