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
        Schema::table('subjek', function (Blueprint $table) {
            $table->foreignId('opd_provinsi_id')->references('id')->on('opd_provinsi')->onDelete('cascade')->after('nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjek', function (Blueprint $table) {
            //
        });
    }
};
