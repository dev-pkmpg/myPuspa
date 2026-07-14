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
    Route::get('/absensi/mesin', fn () => view('pages.attendance.zkteco'))->name('attendance.mesin');

    // Booking — all authenticated users
    Route::prefix('booking')->name('booking.')->group(function () {
        Route::get('/buat', fn () => view('pages.booking.form'))->name('form');
        Route::get('/riwayat', fn () => view('pages.booking.history'))->name('history');
    });

    // Manager only
    Route::prefix('manager')->name('manager.')->middleware('role:manager')->group(function () {
        Route::get('/booking', fn () => view('pages.manager.booking'))->name('booking.index');
    });

    // Admin only
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/ruangan', fn () => view('pages.admin.ruangan'))->name('ruangan.index');
        Route::get('/absensi', fn () => view('pages.attendance.admin'))->name('attendance.index');
        Route::get('/pengaturan-jam', fn () => view('pages.settings.shifts'))->name('settings.shifts');
        Route::get('/hari-libur', fn () => view('pages.settings.holidays'))->name('settings.holidays');

        Route::prefix('kepegawaian')->name('kepegawaian.')->group(function () {
            Route::get('/', fn () => view('pages.kepegawaian.employees'))->name('employees');
            Route::get('/jabatan', fn () => view('pages.kepegawaian.jabatan'))->name('jabatan');
            Route::get('/status-pegawai', fn () => view('pages.kepegawaian.status-pegawai'))->name('status-pegawai');
            Route::get('/klaster', fn () => view('pages.kepegawaian.klaster'))->name('klaster');
            Route::get('/lokasi', fn () => view('pages.kepegawaian.lokasi'))->name('lokasi');
        });
    });
});

require __DIR__ . '/auth.php';
