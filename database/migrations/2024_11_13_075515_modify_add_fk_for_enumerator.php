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

        Schema::table('enumerator_kabupaten', function (Blueprint $table) {
            // $table->dropColumn('opd_kabupaten_id');
            // $table->dropColumn('enumerator_id');
            $table->foreignId('enumerator_id')->references('id')->on('enumerator')->onDelete('cascade');
            $table->foreignId('opd_kabupaten_id')->references('id')->on('opd_kabupaten')->onDelete('cascade');
        });

        Schema::table('enumerator_provinsi', function (Blueprint $table) {
            // $table->dropColumn('opd_provinsi_id');
            // $table->dropColumn('enumerator_id');
            $table->foreignId('enumerator_id')->references('id')->on('enumerator')->onDelete('cascade');
            $table->foreignId('opd_provinsi_id')->references('id')->on('opd_provinsi')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enumerator_kabupaten', function (Blueprint $table) {
            $table->dropForeign(['enumerator_id']);
            $table->dropForeign(['opd_kabupaten_id']);
        });

        Schema::table('enumerator_provinsi', function (Blueprint $table) {
            $table->dropForeign(['enumerator_id']);
            $table->dropForeign(['opd_provinsi_id']);
        });
    }
};
