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
    
    // 1. Dashboard Utama
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 2. Route Program & Kegiatan
    Route::get('/program-kegiatan', [KegiatanController::class, 'index'])->name('kegiatan.index');

    // 3. Fitur Input Realisasi (Khusus Operator & Admin)
    Route::middleware(['role_or_permission:Operator|Admin|input realisasi'])->group(function () {
        Route::get('/input-realisasi', [RealisasiController::class, 'create'])->name('realisasi.create');
        Route::post('/input-realisasi', [RealisasiController::class, 'store'])->name('realisasi.store');
        
        // [FITUR ROADMAP]: Tambahkan Route DELETE di sini agar Operator/Admin bisa menghapus
        Route::delete('/realisasi/{id}', [RealisasiController::class, 'destroy'])->name('realisasi.destroy');
        Route::get('/realisasi/antrean-approval', [RealisasiController::class, 'approvalQueue'])->name('realisasi.approval.queue');
        Route::post('/realisasi/approval/{id}', [RealisasiController::class, 'processApproval'])->name('realisasi.approval.process');
        Route::put('/realisasi/update/{id}', [RealisasiController::class, 'update'])->name('realisasi.update');

        // Rute proses simpan manual dan import excel
        Route::post('/program-kegiatan/manual', [KegiatanController::class, 'storeManual'])->name('kegiatan.storeManual');
        Route::post('/program-kegiatan/import', [KegiatanController::class, 'importExcel'])->name('kegiatan.importExcel');
    });

    // 4. Fitur Laporan Bulanan
    // [PERBAIKAN]: Diarahkan ke LaporanController agar logic filter periode berjalan 100%
    Route::get('/laporan-bulanan', [LaporanController::class, 'index'])->name('laporan.index');

    // 5. RUTE PROFILE 
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/log-sistem', [LaporanController::class, 'indexLog'])->name('log.index');
});

require __DIR__.'/auth.php';