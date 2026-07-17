<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('programs')->insert([
            [
                'kode_program' => 'PRG.01',
                'nama_program' => 'Program Dukungan Manajemen dan Pelaksanaan Tugas Teknis',
                'tahun_anggaran' => 2026,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode_program' => 'PRG.02',
                'nama_program' => 'Program Peningkatan Sarana dan Prasarana Aparatur',
                'tahun_anggaran' => 2026,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode_program' => 'PRG.03',
                'nama_program' => 'Program Pengembangan Sistem Monitoring Anggaran Digital',
                'tahun_anggaran' => 2026,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}