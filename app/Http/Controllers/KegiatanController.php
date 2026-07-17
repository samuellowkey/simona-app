<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KegiatanController extends Controller
{
    public function index()
    {
        // 1. Ambil semua kegiatan beserta informasi programnya
        $kegiatanData = DB::table('kegiatan')
            ->join('programs', 'kegiatan.program_id', '=', 'programs.id')
            ->select([
                'kegiatan.*',
                'programs.nama_program',
                'programs.kode_program',
                'programs.tahun_anggaran'
            ])
            ->orderBy('kegiatan.kode_kegiatan', 'asc')
            ->get();

        // 2. Ambil data program untuk drop-down pilihan di Form Manual
        $programList = DB::table('programs')->get();

        return view('program-kegiatan', compact('kegiatanData', 'programList'));
    }

    // 📥 PROSES INPUT MANUAL
    public function storeManual(Request $request)
    {
        $request->validate([
            'program_id' => 'required',
            'kode_kegiatan' => 'required',
            'nama_kegiatan' => 'required',
            'pagu_anggaran' => 'required|numeric',
            'target_serapan_persen' => 'required|numeric|max:100',
            // Validasi kondisional, hanya wajib diisi jika program_id bernilai 'OTHER'
            'new_kode_program' => 'required_if:program_id,OTHER|nullable|string',
            'new_nama_program' => 'required_if:program_id,OTHER|nullable|string',
            'new_tahun_anggaran' => 'required_if:program_id,OTHER|nullable|numeric',
        ]);

        DB::beginTransaction();
        try {
            $programId = $request->program_id;

            // JIKA USER MEMILIH MENU INPUT PROGRAM INDUK BARU
            if ($request->program_id === 'OTHER') {
                $programId = DB::table('programs')->insertGetId([
                    'kode_program' => strtoupper($request->new_kode_program),
                    'nama_program' => $request->new_nama_program,
                    'tahun_anggaran' => $request->new_tahun_anggaran ?? 2026,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Fitur Audit Trail: Catat aksi tambah program baru ini ke Log Aktivitas
                DB::table('audit_logs')->insert([
                    'user_id' => auth()->id(),
                    'aktivitas' => 'TAMBAH_PROGRAM_BARU',
                    'deskripsi' => 'User membuat Master Program Induk baru: ' . $request->new_nama_program . ' (' . strtoupper($request->new_kode_program) . ')',
                    'ip_address' => $request->ip(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // SIMPAN DATA KEGIATAN BARU
            DB::table('kegiatan')->insert([
                'program_id' => $programId,
                'kode_kegiatan' => $request->kode_kegiatan,
                'nama_kegiatan' => $request->nama_kegiatan,
                'pagu_anggaran' => $request->pagu_anggaran,
                'target_serapan_persen' => $request->target_serapan_persen,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Data Pagu Kegiatan berhasil ditambahkan!');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors(['error' => 'Gagal menambahkan data: ' . $e->getMessage()]);
        }
    }

    // 📤 PROSES IMPORT FILE (Format CSV)
    public function importExcel(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('file_excel');
        $handle = fopen($file->getRealPath(), 'r');
        
        // Lewati baris pertama (header kolom Excel)
        fgetcsv($handle, 1000, ',');

        // Looping baca baris data excel
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            // Skema kolom di CSV: [0]kode_program, [1]kode_kegiatan, [2]nama_kegiatan, [3]pagu, [4]target
            $program = DB::table('programs')->where('kode_program', $data[0])->first();

            if ($program) {
                DB::table('kegiatan')->insert([
                    'program_id' => $program->id,
                    'kode_kegiatan' => $data[1],
                    'nama_kegiatan' => $data[2],
                    'pagu_anggaran' => $data[3],
                    'target_serapan_persen' => $data[4],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        fclose($handle);
        return redirect()->back()->with('success', 'Data Excel Pagu Anggaran berhasil di-import!');
    }
}