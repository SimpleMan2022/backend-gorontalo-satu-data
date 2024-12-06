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
        Schema::table('enumerator', function (Blueprint $table) {
            $table->unsignedBigInteger('wilayah_id')->nullable()->after('jabatan');
            $table->string('tipe_wilayah')->default('App\\Models\\Desa')->after('jabatan');
        });


        Schema::table('user_opd_kabupaten', function (Blueprint $table) {
            $table->unsignedBigInteger('wilayah_id')->nullable()->after('jabatan');
            $table->string('tipe_wilayah')->default('App\\Models\\Kecamatan')->nullable()->after('jabatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enumerator', function (Blueprint $table) {
            //
        });
    }
};
