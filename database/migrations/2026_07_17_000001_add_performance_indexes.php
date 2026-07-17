<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan index pada kolom yang sering digunakan dalam query filter
 * untuk meningkatkan performa query di tabel realisasi dan audit_logs.
 *
 * Kolom yang diindeks:
 * - realisasi.status         → sering di-filter (WHERE status = 'approved')
 * - realisasi.tanggal_realisasi → sering di-filter (WHERE YEAR/MONTH)
 * - realisasi.kegiatan_id    → sudah ada FK index, tambahan composite
 * - audit_logs.aktivitas     → sering di-filter di halaman log
 * - audit_logs.user_id       → sudah ada FK index
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('realisasi', function (Blueprint $table) {
            // Index untuk filter status (approved/pending/rejected)
            $table->index('status', 'idx_realisasi_status');

            // Index untuk filter tanggal (WHERE YEAR, WHERE MONTH, WHERE BETWEEN)
            $table->index('tanggal_realisasi', 'idx_realisasi_tanggal');

            // Composite index untuk query sisa pagu — kegiatan_id + status
            $table->index(['kegiatan_id', 'status'], 'idx_realisasi_kegiatan_status');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            // Index untuk filter jenis aktivitas di log viewer
            $table->index('aktivitas', 'idx_audit_logs_aktivitas');

            // Index untuk filter tanggal di log viewer
            $table->index('created_at', 'idx_audit_logs_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('realisasi', function (Blueprint $table) {
            $table->dropIndex('idx_realisasi_status');
            $table->dropIndex('idx_realisasi_tanggal');
            $table->dropIndex('idx_realisasi_kegiatan_status');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_aktivitas');
            $table->dropIndex('idx_audit_logs_created_at');
        });
    }
};
