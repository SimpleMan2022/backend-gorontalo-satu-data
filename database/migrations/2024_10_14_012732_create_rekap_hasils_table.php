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
        Schema::create('rekap_hasil', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->foreignId('enumerator_id')->references('id')->on('enumerator')->onDelete('cascade');
            $table->foreignId('periode_id')->references('id')->on('periode')->onDelete('cascade');
            $table->enum('status',['v2','v3','tolak','revisi','terima']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_hasils');
    }
};
