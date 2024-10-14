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
        Schema::create('hasil_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variabel_id')->references('id')->on('variabel')->onDelete('cascade');
            $table->foreignId('rekap_hasil_id')->references('id')->on('rekap_hasil')->onDelete('cascade');
            $table->string('konten');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_data');
    }
};
