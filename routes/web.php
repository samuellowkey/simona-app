<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RealisasiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    // 1. Dashboard Utama — semua role bisa akses
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 2. Laporan Bulanan — semua role bisa lihat
    Route::get('/laporan-bulanan', [LaporanController::class, 'index'])->name('laporan.index');

    // 3. Program & Kegiatan — semua role bisa lihat
    Route::get('/program-kegiatan', [KegiatanController::class, 'index'])->name('kegiatan.index');

    // 4. Input & Kelola Realisasi — HANYA Operator & Admin
    // Pimpinan tidak bisa input, hanya approve
    Route::middleware(['role_or_permission:Operator|Admin|input realisasi'])->group(function () {
        Route::get('/input-realisasi', [RealisasiController::class, 'create'])->name('realisasi.create');
        Route::post('/input-realisasi', [RealisasiController::class, 'store'])->name('realisasi.store');
        Route::delete('/realisasi/{id}', [RealisasiController::class, 'destroy'])->name('realisasi.destroy');
        Route::put('/realisasi/update/{id}', [RealisasiController::class, 'update'])->name('realisasi.update');

        // Input data Program & Kegiatan
        Route::post('/program-kegiatan/manual', [KegiatanController::class, 'storeManual'])->name('kegiatan.storeManual');
        Route::post('/program-kegiatan/import', [KegiatanController::class, 'importExcel'])->name('kegiatan.importExcel');
        
        // Edit & Hapus Pagu Kegiatan
        Route::put('/kegiatan/{id}', [KegiatanController::class, 'update'])->name('kegiatan.update');
        Route::delete('/kegiatan/{id}', [KegiatanController::class, 'destroy'])->name('kegiatan.destroy');
    });

    // 5. Approval Realisasi — HANYA Admin & Pimpinan (bukan Operator)
    // SECURITY: Operator tidak boleh approve agar mencegah self-approval
    Route::middleware(['role_or_permission:Admin|Pimpinan|Validasi Laporan'])->group(function () {
        Route::get('/realisasi/antrean-approval', [RealisasiController::class, 'approvalQueue'])->name('realisasi.approval.queue');
        Route::post('/realisasi/approval/{id}', [RealisasiController::class, 'processApproval'])->name('realisasi.approval.process');
    });

    // 6. Log Sistem — Hanya Admin
    Route::middleware(['role:Admin'])->group(function () {
        Route::get('/log-sistem', [LaporanController::class, 'indexLog'])->name('log.index');
        Route::delete('/program-kegiatan/{id}', [KegiatanController::class, 'destroy'])->name('kegiatan.destroy');
    Route::delete('/program-induk/{id}', [KegiatanController::class, 'destroyProgram'])->name('program.destroy');
    });

    // 7. Profile Management — semua user yang login
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';