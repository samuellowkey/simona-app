<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('realisasi', function (Blueprint $table) {
            // Kita buat nullable agar data lama tidak error/patah
            $table->string('bukti_nota')->nullable()->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('realisasi', function (Blueprint $table) {
            $table->dropColumn('bukti_nota');
        });
    }
};