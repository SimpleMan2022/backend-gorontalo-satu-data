<?php

use App\Http\Controllers\AntarWaktuController;
use App\Http\Controllers\ArtikelKawasanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BeritaAcaraController;
use App\Http\Controllers\PeriodeController;
use App\Http\Controllers\SektorController;
use App\Http\Controllers\UserOpdKabupatenController;
use App\Http\Controllers\UserOpdProvinsiController;
use App\Http\Controllers\OpdProvinsiController;
use App\Http\Controllers\OpdKabupatenController;
use App\Http\Controllers\IndikatorController;
use App\Http\Controllers\VariabelController;
use App\Http\Controllers\SubjekController;
use App\Models\BeritaAcara;
use Illuminate\Support\Facades\Route;

Route::prefix("auth")->controller(AuthController::class)->group(function () {
    Route::post("register", "create");
    Route::post("login", "login")->name('login');
});

Route::group(['middleware' => 'auth:sanctum'], function () {

    // Auth Route
    Route::controller(AuthController::class)->group(function () {
        Route::get("me", "getUser");
        Route::post("logout", "logout");
    });

    // Sektor Route
    Route::prefix('sektor')->controller(SektorController::class)->group(function () {
        Route::get("", "index")->name('sektor.index');
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{sektor}", "update");
        Route::delete("/delete/{sektor}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    // Antar Waktu
    Route::prefix('antarwaktu')->controller(AntarWaktuController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        // Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    // Indikator
    Route::prefix('indikator')->controller(IndikatorController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/by-sektor/{sektor}", "bySektor");
        Route::get("/by-antarwaktu/{antar_waktu}", "byAntarWaktu");
        Route::get("/by-opd/{opd_provinsi}", "byOpdProvinsi");
        Route::get("/{id}", "show");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    // Periode
    Route::prefix('periode')->controller(PeriodeController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    // Subjek
    Route::prefix('subjek')->controller(SubjekController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::post("/multi-store", "multiStore");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/delete-pivot/{id}", "destroyPivot");
    });

    Route::prefix('variabel')->controller(VariabelController::class)->group(function () {
        Route::get("", "index");
        Route::get("/by-indikator/{indikator}", "byIndikator");
        Route::patch("/tukar-posisi/{id}", "tukarPosisi");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::get("byOpdProvinsi/{id}", "byOpdProvinsi");
    });

    // User Opd Provinsi
    Route::prefix('user_opd_provinsi')->controller(UserOpdProvinsiController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::get("byOpdKabupaten/{id}", "byOpdKabupaten");
        Route::get("by-opd-provinsi/{id}", "byOpdProvinsi");
    });
    // User Opd Kabupaten
    Route::prefix('user_opd_kabupaten')->controller(UserOpdKabupatenController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::get("by-opd-kabupaten/{id}", "byOpdKabupaten");
    });

    Route::prefix('opd_provinsi')->controller(OpdProvinsiController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
    });

    Route::prefix('artikel-kawasan')->controller(ArtikelKawasanController::class)->group(function () {
        // Route::get("", "index");
        Route::post("", "store");
    });

    Route::prefix('opd_kabupaten')->controller(OpdKabupatenController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::get("by-opd-provinsi/{opd_provinsi}", "byOpdKabupaten");
    });

    Route::prefix('berita_acara')->controller(BeritaAcaraController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
    });
});
