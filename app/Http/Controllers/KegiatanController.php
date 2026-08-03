<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\Kegiatan;
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
            \Illuminate\Support\Facades\Log::error('Gagal tambah kegiatan manual: ' . $e->getMessage(), ['user_id' => auth()->id()]);
            return redirect()->back()->withErrors(['error' => 'Gagal menambahkan data. Silakan hubungi Administrator.']);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'program_id' => 'required',
            'kode_kegiatan' => 'required',
            'nama_kegiatan' => 'required',
            'pagu_anggaran' => 'required|numeric',
            'target_serapan_persen' => 'required|numeric',
        ]);

        $affected = DB::table('kegiatan')
            ->where('id', $id)
            ->update([
                'program_id'            => $request->program_id,
                'kode_kegiatan'         => $request->kode_kegiatan,
                'nama_kegiatan'         => $request->nama_kegiatan,
                'pagu_anggaran'         => $request->pagu_anggaran,
                'target_serapan_persen' => $request->target_serapan_persen,
                'updated_at'            => now(),
            ]);

        if (!$affected) {
            return redirect()->back()->withErrors(['error' => 'Data kegiatan gagal diperbarui atau tidak ditemukan.']);
        }

        return redirect()->back()->with('success', 'Pagu kegiatan berhasil diperbarui!');
    }

    public function destroy($id)
    {
        // 1. Ambil data kegiatan sebelum dihapus untuk disimpan info audit-nya
        $kegiatan = DB::table('kegiatan')->where('id', $id)->first();

        if (!$kegiatan) {
            return redirect()->back()->withErrors(['error' => 'Data kegiatan tidak ditemukan.']);
        }

        DB::beginTransaction();
        try {
            // Ambil nama user yang sedang login
            $userName = auth()->user()->nama_lengkap ?? auth()->user()->name ?? 'User';
            $paguFormatted = 'Rp ' . number_format($kegiatan->pagu_anggaran, 0, ',', '.');

            // 2. Hapus data kegiatan
            DB::table('kegiatan')->where('id', $id)->delete();

            // 3. Catat ke Audit Log
            // Jika menggunakan Model LogAktivitas:
            if (class_exists(LogAktivitas::class)) {
                LogAktivitas::catat(
                    'HAPUS_PAGU',
                    "menghapus Pagu Kegiatan: {$kegiatan->nama_kegiatan} ({$kegiatan->kode_kegiatan}) sebesar {$paguFormatted}"
                );
            } else {
                // Atau jika menggunakan DB Table langsung (sesuai kodingan storeManual kamu):
                DB::table('audit_logs')->insert([
                    'user_id'    => auth()->id(),
                    'aktivitas'  => 'HAPUS_PAGU',
                    'deskripsi'  => "User {$userName} menghapus Pagu Kegiatan: {$kegiatan->nama_kegiatan} ({$kegiatan->kode_kegiatan}) sebesar {$paguFormatted}",
                    'ip_address' => request()->ip() ?? '127.0.0.1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Pagu kegiatan berhasil dihapus!');

        } catch (\Exception $e) {
            DB::rollback();
            \Illuminate\Support\Facades\Log::error('Gagal hapus kegiatan: ' . $e->getMessage(), ['user_id' => auth()->id()]);
            return redirect()->back()->withErrors(['error' => 'Gagal menghapus data kegiatan.']);
        }
    }

    public function destroyProgram($id)
    {
        $program = DB::table('programs')->where('id', $id)->first();

        if (!$program) {
            return redirect()->back()->withErrors(['error' => 'Data program tidak ditemukan.']);
        }

        DB::beginTransaction();
        try {
            $userName = auth()->user()->nama_lengkap ?? auth()->user()->name ?? 'User';

            // Hapus Master Program
            DB::table('programs')->where('id', $id)->delete();

            // Catat Audit Log
            if (class_exists(LogAktivitas::class)) {
                LogAktivitas::catat(
                    'HAPUS_PROGRAM',
                    "menghapus Master Program Induk: {$program->nama_program} ({$program->kode_program})"
                );
            } else {
                DB::table('audit_logs')->insert([
                    'user_id'    => auth()->id(),
                    'aktivitas'  => 'HAPUS_PROGRAM',
                    'deskripsi'  => "User {$userName} menghapus Master Program Induk: {$program->nama_program} ({$program->kode_program})",
                    'ip_address' => request()->ip() ?? '127.0.0.1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Master Program Induk berhasil dihapus!');

        } catch (\Exception $e) {
            DB::rollback();
            \Illuminate\Support\Facades\Log::error('Gagal hapus program: ' . $e->getMessage(), ['user_id' => auth()->id()]);
            return redirect()->back()->withErrors(['error' => 'Gagal menghapus program. Pastikan tidak ada kegiatan yang terikat dengan program ini.']);
        }
    }

    // 📤 PROSES IMPORT FILE (Format CSV)
    public function importExcel(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:csv,txt|max:4096'
        ]);

        $file = $request->file('file_excel');
        $handle = fopen($file->getRealPath(), 'r');
        
        // Lewati baris pertama (header kolom Excel)
        fgetcsv($handle, 1000, ',');

        DB::beginTransaction();
        try {
            $importedCount = 0;
            // Looping baca baris data excel
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                // Pastikan jumlah kolom minimal 5
                if (count($data) < 5) {
                    continue;
                }

                // Skema kolom di CSV: [0]kode_program, [1]kode_kegiatan, [2]nama_kegiatan, [3]pagu, [4]target
                $kodeProgram = trim($data[0]);
                $kodeKegiatan = trim($data[1]);
                $namaKegiatan = trim($data[2]);
                $paguAnggaran = filter_var($data[3], FILTER_VALIDATE_INT);
                $targetSerapan = filter_var($data[4], FILTER_VALIDATE_FLOAT);

                // Skip jika data wajib kosong atau format angka salah
                if (empty($kodeProgram) || empty($kodeKegiatan) || empty($namaKegiatan) || $paguAnggaran === false || $targetSerapan === false) {
                    continue;
                }

                $program = DB::table('programs')->where('kode_program', $kodeProgram)->first();

                if ($program) {
                    DB::table('kegiatan')->insert([
                        'program_id' => $program->id,
                        'kode_kegiatan' => $kodeKegiatan,
                        'nama_kegiatan' => $namaKegiatan,
                        'pagu_anggaran' => $paguAnggaran,
                        'target_serapan_persen' => min(100, max(0, $targetSerapan)),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $importedCount++;
                }
            }

            fclose($handle);

            if ($importedCount === 0) {
                DB::rollback();
                return redirect()->back()->withErrors(['error' => 'Tidak ada data valid yang berhasil di-import. Pastikan kode program terdaftar.']);
            }

            // Catat log aktivitas
            DB::table('audit_logs')->insert([
                'user_id' => auth()->id(),
                'aktivitas' => 'IMPORT_PAGU_CSV',
                'deskripsi' => 'User berhasil meng-import ' . $importedCount . ' data Pagu Kegiatan via CSV',
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Berhasil meng-import ' . $importedCount . ' data Pagu Anggaran!');
        } catch (\Exception $e) {
            DB::rollback();
            if (is_resource($handle)) {
                fclose($handle);
            }
            \Illuminate\Support\Facades\Log::error('Gagal import CSV pagu: ' . $e->getMessage(), ['user_id' => auth()->id()]);
            return redirect()->back()->withErrors(['error' => 'Gagal memproses file. Pastikan format CSV sesuai ketentuan.']);
        }
    }
}