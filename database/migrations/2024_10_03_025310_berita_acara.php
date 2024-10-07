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
        Schema::create('berita_acara', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->foreignId('opd_kabupaten_id')->references('id')->on('opd_kabupaten')->onDelete('cascade');
            $table->string('file');
            $table->foreignId('indikator_id')->references('id')->on('indikator')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('berita_acara');
    }
};
