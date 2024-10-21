<?php

use App\Http\Controllers\AntarWaktuController;
use App\Http\Controllers\ArtikelKawasanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BeritaAcaraController;
use App\Http\Controllers\EnumeratorController;
use App\Http\Controllers\EnumeratorKabupatenController;
use App\Http\Controllers\EnumeratorProvinsiController;
use App\Http\Controllers\PeriodeController;
use App\Http\Controllers\SektorController;
use App\Http\Controllers\UserOpdKabupatenController;
use App\Http\Controllers\UserOpdProvinsiController;
use App\Http\Controllers\OpdProvinsiController;
use App\Http\Controllers\OpdKabupatenController;
use App\Http\Controllers\IndikatorController;
use App\Http\Controllers\VariabelController;
use App\Http\Controllers\SubjekController;
use App\Http\Controllers\PivotOpdSektorController;
use App\Http\Controllers\HasilDataController;
use App\Http\Controllers\PivotEnumeratorIndikatorController;
use Illuminate\Support\Facades\Route;

Route::prefix("auth")->controller(AuthController::class)->group(function () {
    Route::post("register", "create");
    Route::post("login", "login")->name('login');
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    // Auth Route
    Route::controller(AuthController::class)->group(function () {
        Route::get("auth/me", "getUser");
        Route::post("auth/logout", "logout");
    });

    // Sektor Route
    Route::prefix('sektor')->controller(SektorController::class)->group(function () {
        Route::get("", "index")->name('sektor.index');
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::post("/edit/{sektor}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    // Antar Waktu
    Route::prefix('antar-waktu')->controller(AntarWaktuController::class)->group(function () {
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
        Route::patch("/{id}/status", "updateStatus");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    // Periode
    Route::prefix('periode')->controller(PeriodeController::class)->group(function () {
        Route::get("", "index");
        Route::get("/aktif", "aktif");
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
        Route::delete("/multi-delete", "multiDestroy");
    });

    // Subjek
    Route::prefix('pivot-enumerator-indikator')->controller(PivotEnumeratorIndikatorController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::post("/multi-store", "multiStore");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
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
        Route::delete("/multi-delete", "multiDestroy");
    });

    // User Opd Provinsi
    Route::prefix('user-opd-provinsi')->controller(UserOpdProvinsiController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::get("byOpdKabupaten/{id}", "byOpdKabupaten");
        Route::get("by-opd-provinsi/{id}", "byOpdProvinsi");
        Route::delete("/multi-delete", "multiDestroy");
    });
    // User Opd Kabupaten
    Route::prefix('user-opd-kabupaten')->controller(UserOpdKabupatenController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::get("by-opd-kabupaten/{id}", "byOpdKabupaten");
        Route::delete("/multi-delete", "multiDestroy");
    });

    Route::prefix('opd-provinsi')->controller(OpdProvinsiController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::post("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    Route::prefix('artikel-kawasan')->controller(ArtikelKawasanController::class)->group(function () {
        Route::post("", "store");
        Route::post("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    Route::prefix('opd-kabupaten')->controller(OpdKabupatenController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::post("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::get("by-opd-provinsi/{opd_provinsi}", "byOpdKabupaten");
        Route::delete("/multi-delete", "multiDestroy");
    });

    Route::prefix('berita-acara')->controller(BeritaAcaraController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::post("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    Route::prefix('enumerator')->controller(EnumeratorController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    Route::prefix('enumerator-kabupaten')->controller(EnumeratorKabupatenController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    Route::prefix('enumerator-provinsi')->controller(EnumeratorProvinsiController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });


    Route::prefix('pivot-opd-sektor')->controller(PivotOpdSektorController::class)->group(function () {
        Route::get("", "index");
        Route::get("/by-opd/{id}", "byOpd");
        Route::get("/by-sektor/{id}", "bySektor");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    Route::prefix('hasil-data')->controller(HasilDataController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });
});

Route::get('/docs', function () {
    return redirect('/api/documentation');
});
