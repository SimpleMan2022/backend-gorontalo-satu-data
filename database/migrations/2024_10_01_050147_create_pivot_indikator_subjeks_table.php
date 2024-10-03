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
        Schema::create('pivot_indikator_subjek', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indikator_id')->references('id')->on('indikator')->onDelete('cascade');
            $table->foreignId('subjek_id')->references('id')->on('subjek')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pivot_indikator_subjeks');
    }
};
