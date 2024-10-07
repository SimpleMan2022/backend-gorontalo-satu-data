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
        Schema::create('artikel_kawasan', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('file');
            $table->text('konten');
            $table->enum('status', ['public', 'private']);
            $table->foreignId('opd_provinsi_id')->references('id')->on('opd_provinsi')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artikel');
    }
};
