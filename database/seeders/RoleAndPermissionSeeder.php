<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache permission bawaan Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. DAFTAR PERMISSION (Ditambah 'Validasi Laporan' untuk sistem Approval)
        $p1 = Permission::firstOrCreate(['name' => 'kelola kegiatan']);
        $p2 = Permission::firstOrCreate(['name' => 'input realisasi']);
        $p3 = Permission::firstOrCreate(['name' => 'lihat laporan']);
        $p4 = Permission::firstOrCreate(['name' => 'Validasi Laporan']); // <-- KUNCI APPROVAL BRAY!

        // 2. SETTING ROLE & ASSIGN PERMISSION
        // Admin dapat semua hak akses
        $roleAdmin = Role::firstOrCreate(['name' => 'Admin']);
        $roleAdmin->givePermissionTo(Permission::all());

        // Operator hanya bisa input dan lihat laporan
        $roleOperator = Role::firstOrCreate(['name' => 'Operator']);
        $roleOperator->givePermissionTo([$p2, $p3]);

        // Pimpinan / Kasubag bisa lihat laporan dan berhak melakukan Approval
        $rolePimpinan = Role::firstOrCreate(['name' => 'Pimpinan']);
        $rolePimpinan->givePermissionTo([$p3, $p4]); // <-- Kasih izin validasi laporan

        // 3. GENERATE MASTER USER AWAL
        // Akun Master Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@simona.go.id'],
            [
                'username' => 'admin',               
                'nama_lengkap' => 'Administrator SIMONA', 
                'password' => Hash::make('SimonaAdmin2026!'), // <-- Ganti password yang lebih kuat bray
            ]
        );
        if (!$admin->hasRole('Admin')) {
            $admin->assignRole($roleAdmin);
        }

        // Akun Contoh Operator (Ganti password sebelum deploy ke production!)
        // Password default bisa diatur via environment: OPERATOR_DEFAULT_PASSWORD
        $operator = User::firstOrCreate(
            ['email' => 'operator@simona.go.id'],
            [
                'username'     => 'operator',
                'nama_lengkap' => 'Staf Keuangan Operator',
                'password'     => Hash::make(env('OPERATOR_DEFAULT_PASSWORD', 'SimonaOpr2026!@#')),
            ]
        );
        if (!$operator->hasRole('Operator')) {
            $operator->assignRole($roleOperator);
        }
    }
}