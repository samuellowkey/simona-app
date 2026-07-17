<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('realisasi', function (Blueprint $table) {
            // Default 'pending' ketika operator baru input data
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('progres_fisik_percent');
            $table->string('catatan_reject')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('realisasi', function (Blueprint $table) {
            $table->dropColumn(['status', 'catatan_reject']);
        });
    }
};