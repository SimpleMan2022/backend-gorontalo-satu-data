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
        Schema::table('enumerator_provinsi', function (Blueprint $table) {
            // $table->dropForeign(['enumerator_id']);
            $table->dropColumn('enumerator_id');


            // Drop foreign key dan kolom periode_id yang lama
            // $table->dropForeign(['opd_kabupaten_id']);
            $table->dropColumn('opd_provinsi_id');

            // Menambahkan kembali kolom enumerator_id dengan onDelete yang baru
            $table->foreignId('enumerator_id')->nullable()->constrained('enumerator')->onDelete('cascade');

            // Menambahkan kembali kolom periode_id dengan onDelete yang baru
            $table->foreignId('opd_provinsi_id')->nullable()->constrained('opd_provinsi')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enumerator_provinsi', function (Blueprint $table) {
            // $table->dropForeign(['enumerator_id']);
            $table->dropColumn('enumerator_id');

            // Menghapus foreign key dan kolom periode_id yang baru
            // $table->dropForeign(['opd_kabupaten_id']);
            $table->dropColumn('opd_provinsi_id');

            // Menambahkan kembali kolom enumerator_id dengan pengaturan onDelete lama
            $table->foreignId('enumerator_id')->constrained('enumerator')->onDelete('cascade');

            // Menambahkan kembali kolom periode_id dengan pengaturan onDelete lama
            $table->foreignId('opd_provinsi_id')->constrained('opd_provinsi')->onDelete('cascade');
        });
    }
};
