<?php

use App\Http\Controllers\AntarWaktuController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SektorController;
use App\Http\Controllers\UserOpdKabupatenController;
use App\Http\Controllers\UserOpdProvinsiController;
use App\Http\Controllers\OpdProvinsiController;
use App\Http\Controllers\OpdKabupatenController;
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

});
// User Opd Provinsi
Route::prefix('user_opd_provinsi')->controller(UserOpdProvinsiController::class)->group(function () {
    Route::get("", "index");
    Route::post("", "store");
    Route::get("/{id}", "show");
    Route::patch("/edit/{id}", "update");
    Route::delete("/delete/{id}", "destroy");
    Route::get("byOpdProvinsi/{id}", "byOpdProvinsi");
});

// User Opd Kabupaten
Route::prefix('user_opd_kabupaten')->controller(UserOpdKabupatenController::class)->group(function () {
    Route::get("", "index");
    Route::post("", "store");
    Route::get("/{id}", "show");
    Route::patch("/edit/{id}", "update");
    Route::delete("/delete/{id}", "destroy");
    Route::get("byOpdKabupaten/{id}", "byOpdKabupaten");
});

Route::prefix('opd_provinsi')->controller(OpdProvinsiController::class)->group(function () {
    Route::get("", "index");
    Route::post("", "store");
    Route::get("/{id}", "show");
    Route::patch("/edit/{id}", "update");
    Route::delete("/delete/{id}", "destroy");
});

Route::prefix('opd_kabupaten')->controller(OpdKabupatenController::class)->group(function () {
    Route::get("", "index");
    Route::post("", "store");
    Route::get("/{id}", "show");
    Route::patch("/edit/{id}", "update");
    Route::delete("/delete/{id}", "destroy");
});