<?php

use App\Http\Controllers\AntarWaktuController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BeritaAcaraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataController;
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
use App\Http\Controllers\RekapHasilController;
use App\Http\Controllers\SektorSpasialController;
use App\Http\Controllers\OlahanSpasialController;
use App\Http\Controllers\SpasialKabupatenController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\HasilSpasialController;
use App\Http\Controllers\MobileController;
use Illuminate\Support\Facades\Route;

Route::prefix("auth")->controller(AuthController::class)->group(function () {
    Route::post("register", "create");
    Route::post("login", "login")->name('login');
    Route::post("login-enumerator", "loginEnumerator")->name('login-enumerator');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.reset');
    Route::post('reset-password', [ForgotPasswordController::class, 'resetPassword']);
});

Route::prefix("data")->controller(DataController::class)->group(function () {
    Route::get("kabupaten", "getAllKabupaten");
    Route::get("kecamatan", "getAllKecamatan");
    Route::get("desa", "getAllDesa");
});

Route::get("opd-provinsi", [OpdProvinsiController::class, 'index']);

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
        Route::patch("/edit/{sektor}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    // Antar Waktu
    Route::prefix('antar-waktu')->controller(AntarWaktuController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    // Indikator
    Route::prefix('indikator')->controller(IndikatorController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/update-status/{id}", "updateStatus");
        Route::patch("/multi-update-status", "multiUpdate");
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
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/tukar-posisi/{id}", "tukarPosisi");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
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
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::post("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    Route::prefix('hasil-spasial')->controller(HasilSpasialController::class)->group(function () {
        Route::get("", "index");
        Route::get("/{id}", "show");
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
        Route::get("/by-indikator-mobile", "byIndikatorMobile");
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
        Route::put("/edit/{rekap_hasil_id}", "update");
        // Route::delete("/delete/{id}", "destroy");
    });

    Route::prefix('rekap-hasil')->controller(RekapHasilController::class)->group(function () {
        Route::get("", "index");
        // Route::post("", "store");
        Route::get("/{id}", "show");
        Route::put("/multiVerifikasi", "multiUpdate");
        Route::put("/verifikasi/{rekap_hasil_id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    Route::prefix('sektor-spasial')->controller(SektorSpasialController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });


    Route::prefix('olahan-spasial')->controller(OlahanSpasialController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });


    Route::prefix('spasial-kabupaten')->controller(SpasialKabupatenController::class)->group(function () {
        Route::get("", "index");
        Route::post("", "store");
        Route::get("/{id}", "show");
        Route::patch("/edit/{id}", "update");
        Route::delete("/delete/{id}", "destroy");
        Route::delete("/multi-delete", "multiDestroy");
    });

    Route::prefix('dashboard')->controller(DashboardController::class)->group(function () {
        Route::get("/admin", "AdminView");
        Route::get("/admin/chart-donut", "ChartDonutAdmin");
        Route::get("/admin/chart-bar", "ChartBarAdmin");

        Route::get("/opd-provinsi", "OpdProvinsiView");
        Route::get("/opd-provinsi/chart-donut", "ChartDonutOpdProvinsi");
        Route::get("/opd-provinsi/chart-bar", "ChartBarOpdProvinsi");

        Route::get("/opd-kabupaten", "OpdKabupatenView");
        Route::get("/opd-kabupaten/chart-donut", "ChartDonutOpdKabupaten");
        Route::get("/opd-kabupaten/chart-bar", "ChartBarOpdKabupaten");
    });
});

Route::prefix('mobile')->middleware('auth:sanctum')->group(function () {
    Route::get("/dashboard/enumerator", MobileController::class . "@EnumeratorView");
    Route::get("/dashboard/enumerator/indikator", MobileController::class . "@EnumeratorIndikator");
    Route::get("/profile", AuthController::class . "@getEnumeratorMobile");
});

Route::get('/docs', function () {
    return redirect('/api/documentation');
});
