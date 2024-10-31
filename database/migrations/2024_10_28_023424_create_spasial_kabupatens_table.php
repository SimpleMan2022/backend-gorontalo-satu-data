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
        Schema::create('spasial_kabupaten', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olahan_spasial_id')->references('id')->on('olahan_spasial')->onDelete('cascade');
            $table->foreignId('kabupaten_id')->references('id')->on('regencies')->onDelete('cascade');
            $table->year('tahun');
            $table->string('konten');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spasial_kabupatens');
    }
};
