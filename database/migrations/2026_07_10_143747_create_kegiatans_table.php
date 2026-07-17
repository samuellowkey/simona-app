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
        Schema::create('kegiatan', function (Blueprint $table) {
        $table->id();
        // Relasi ke tabel programs
        $table->foreignId('program_id')->constrained('programs')->onDelete('cascade');
        $table->string('kode_kegiatan')->unique(); // Misal: KGT-01.1
        $table->string('nama_kegiatan');
        $table->bigInteger('pagu_anggaran'); // Menggunakan bigInteger untuk nominal uang besar
        $table->float('target_serapan_persen')->default(100);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kegiatans');
    }
};
