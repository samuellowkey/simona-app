<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('realisasi', function (Blueprint $table) {
        $table->id();
        // Relasi ke tabel kegiatan
        $table->foreignId('kegiatan_id')->constrained('kegiatan')->onDelete('cascade');
        $table->date('tanggal_realisasi');
        $table->bigInteger('nominal_realisasi');
        $table->float('progres_fisik_persen');
        // Relasi ke tabel users (siapa penanggung jawab/yang menginput)
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->text('keterangan')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('realisasis');
    }
};
