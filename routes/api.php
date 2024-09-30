<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SektorController;
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
});
