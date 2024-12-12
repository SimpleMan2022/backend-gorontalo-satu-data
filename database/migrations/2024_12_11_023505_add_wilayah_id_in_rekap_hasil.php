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
            $table->unsignedBigInteger('wilayah_id')->nullable()->after('status');
            $table->string('tipe_wilayah')->default('App\\Models\\Desa')->nullable()->after('status');            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_hasil', function (Blueprint $table) {
            //
        });
    }
};
