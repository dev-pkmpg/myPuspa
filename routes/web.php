<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', fn () => view('pages.dashboard'))->name('dashboard');

    // Pegawai only
    Route::middleware('role:pegawai')->group(function () {
        Route::get('/absensi/clock-in', fn () => view('pages.attendance.clock-in'))->name('attendance.clock-in');
    });

    // Both roles
    Route::get('/absensi/riwayat', fn () => view('pages.attendance.history'))->name('attendance.history');

    // Admin only
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/absensi', fn () => view('pages.attendance.admin'))->name('attendance.index');
        Route::get('/pengaturan-jam', fn () => view('pages.settings.shifts'))->name('settings.shifts');

        Route::prefix('kepegawaian')->name('kepegawaian.')->group(function () {
            Route::get('/', fn () => view('pages.kepegawaian.employees'))->name('employees');
            Route::get('/jabatan', fn () => view('pages.kepegawaian.jabatan'))->name('jabatan');
            Route::get('/status-pegawai', fn () => view('pages.kepegawaian.status-pegawai'))->name('status-pegawai');
        });
    });
});

require __DIR__ . '/auth.php';
