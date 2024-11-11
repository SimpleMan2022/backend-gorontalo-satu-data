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
        Schema::table('rekap_hasil', function (Blueprint $table) {
            $table->dropForeign(['enumerator_id']);
            $table->dropColumn('enumerator_id');

            // Drop foreign key dan kolom periode_id yang lama
            $table->dropForeign(['periode_id']);
            $table->dropColumn('periode_id');

            // Menambahkan kembali kolom enumerator_id dengan onDelete yang baru
            $table->foreignId('enumerator_id')->nullable()->constrained('enumerator')->onDelete('set null');

            // Menambahkan kembali kolom periode_id dengan onDelete yang baru
            $table->foreignId('periode_id')->nullable()->constrained('periode')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_hasil', function (Blueprint $table) {
            //
            // Menghapus foreign key dan kolom enumerator_id yang baru
            $table->dropForeign(['enumerator_id']);
            $table->dropColumn('enumerator_id');

            // Menghapus foreign key dan kolom periode_id yang baru
            $table->dropForeign(['periode_id']);
            $table->dropColumn('periode_id');

            // Menambahkan kembali kolom enumerator_id dengan pengaturan onDelete lama
            $table->foreignId('enumerator_id')->constrained('enumerator')->onDelete('cascade');

            // Menambahkan kembali kolom periode_id dengan pengaturan onDelete lama
            $table->foreignId('periode_id')->constrained('periode')->onDelete('cascade');
        });
    }
};
