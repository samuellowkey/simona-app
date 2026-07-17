<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProyekSimonaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Masukkan Role & User Contoh
        $roleId = DB::table('roles')->insertGetId([
            'nama_role' => 'Kepala Bagian Keuangan',
            'created_at' => now(), 'updated_at' => now()
        ]);

        $userId = DB::table('users')->insertGetId([
            'username' => 'kabag_keuangan',
            'nama_lengkap' => 'Budi Setiawan, S.E.',
            'password' => bcrypt('password123'),
            'role_id' => $roleId,
            'created_at' => now(), 'updated_at' => now()
        ]);

        // 2. Masukkan Program Contoh
        $programId = DB::table('programs')->insertGetId([
            'kode_program' => 'PRG-01',
            'nama_program' => 'Program Optimalisasi Infrastruktur IT & Kompetensi SDM',
            'tahun_anggaran' => 2026,
            'created_at' => now(), 'updated_at' => now()
        ]);

        // 3. Masukkan Kegiatan Contoh
        DB::table('kegiatan')->insert([
            [
                'program_id' => $programId,
                'kode_kegiatan' => 'KGT-01.1',
                'nama_kegiatan' => 'Pemeliharaan Server Kantor',
                'pagu_anggaran' => 500000000, // Rp 500 Juta
                'target_serapan_persen' => 100,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'program_id' => $programId,
                'kode_kegiatan' => 'KGT-01.2',
                'nama_kegiatan' => 'Pengadaan Alat Tulis Kantor',
                'pagu_anggaran' => 150000000, // Rp 150 Juta
                'target_serapan_persen' => 100,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'program_id' => $programId,
                'kode_kegiatan' => 'KGT-01.3',
                'nama_kegiatan' => 'Bimtek Pengelolaan Keuangan',
                'pagu_anggaran' => 200000000, // Rp 200 Juta
                'target_serapan_persen' => 100,
                'created_at' => now(), 'updated_at' => now()
            ]
        ]);
    }
}