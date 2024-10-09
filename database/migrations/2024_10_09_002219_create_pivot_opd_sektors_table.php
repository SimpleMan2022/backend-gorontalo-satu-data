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
        Schema::create('pivot_opd_sektor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_provinsi_id')->references('id')->on('opd_provinsi')->onDelete('cascade');
            $table->foreignId('sektor_id')->references('id')->on('sektor')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pivot_opd_sektors');
    }
};
