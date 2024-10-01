<?php

use App\Http\Controllers\AntarWaktuController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SektorController;
use App\Http\Controllers\UserOpdKabupatenController;
use App\Http\Controllers\UserOpdProvinsiController;
use App\Http\Controllers\IndikatorController;
use App\Http\Controllers\SubjekController;

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
    });

    // Antar Waktu
    Route::prefix('antarwaktu')->controller(AntarWaktuController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
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
    });

    // User Opd Provinsi
    Route::prefix('user_opd_provinsi')->controller(UserOpdProvinsiController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
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
});
