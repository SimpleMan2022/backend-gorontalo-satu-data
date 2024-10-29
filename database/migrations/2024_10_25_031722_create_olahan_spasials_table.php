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
        Schema::create('olahan_spasial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_provinsi_id')->references('id')->on('opd_provinsi')->onDelete('cascade');
            $table->foreignId('sektor_spasial_id')->references('id')->on('sektor_spasial')->onDelete('cascade');
            $table->string('judul');
            $table->text('konten');
            $table->enum('status', ['public', 'private'])->default('private');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olahan_spasials');
    }
};
