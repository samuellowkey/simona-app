<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $periode = $request->input('periode');
        
        // Base Query menggunakan nama tabel asli kamu: 'realisasi' dan 'kegiatan'
        $query = DB::table('realisasi')
            ->join('kegiatan', 'realisasi.kegiatan_id', '=', 'kegiatan.id')
            ->leftJoin('users', 'realisasi.user_id', '=', 'users.id')
            ->select([
                'realisasi.*', 
                'kegiatan.nama_kegiatan', 
                'kegiatan.kode_kegiatan',
                'kegiatan.pagu_anggaran',
                'users.nama_lengkap'
            ]);

        // Logika Filter Periode Anggaran 2026
        if ($periode) {
            if (in_array($periode, ['01', '02', '03'])) {
                // Filter Bulanan
                $query->whereMonth('realisasi.tanggal_realisasi', $periode);
            } elseif ($periode == 't1') {
                // Triwulan I
                $query->whereBetween('realisasi.tanggal_realisasi', ['2026-01-01', '2026-03-31']);
            } elseif ($periode == 't2') {
                // Triwulan II
                $query->whereBetween('realisasi.tanggal_realisasi', ['2026-04-01', '2026-06-30']);
            } elseif ($periode == 't3') {
                // Triwulan III
                $query->whereBetween('realisasi.tanggal_realisasi', ['2026-07-01', '2026-09-30']);
            } elseif ($periode == 't4') {
                // Triwulan IV
                $query->whereBetween('realisasi.tanggal_realisasi', ['2026-10-01', '2026-12-31']);
            }
        }

        $realisasiData = $query->orderBy('realisasi.tanggal_realisasi', 'desc')->get();

        // Menggunakan view 'laporan' sesuai dengan struktur kode aslimu
        return view('laporan', compact('realisasiData'));
    }

    public function indexLog(Request $request)
    {
        // Memulai query builder dari model LogAktivitas (tabel audit_logs)
        $query = LogAktivitas::query();

        // 1. Filter Berdasarkan Jenis Aktivitas (jika dipilih)
        if ($request->filled('aktivitas')) {
            $query->where('aktivitas', $request->aktivitas);
        }

        // 2. Filter Berdasarkan Tanggal Spesifik (jika diisi)
        if ($request->filled('tanggal')) {
            $query->whereDate('created_at', $request->tanggal);
        }

        // Ambil data log terbaru dengan pagination (10 data per halaman)
        // appends(request()->query()) memastikan parameter filter tidak hilang saat klik pindah halaman (Next/Prev)
        $logs = $query->latest()->paginate(10)->appends($request->query()); 

        return view('log.index', compact('logs'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal_realisasi' => 'required|date',
            'progres_fisik'     => 'required|numeric|min:0|max:100',
            'nominal_realisasi' => 'required|numeric|min:1',
            'keterangan'        => 'required|string',
            'bukti_nota'        => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        // 1. Cari data lama di database
        $realisasi = DB::table('realisasi')->where('id', $id)->first();
        if (!$realisasi) {
            return redirect()->back()->withErrors(['error' => 'Data tidak ditemukan bray!']);
        }

        $updateData = [
            'tanggal_realisasi'     => $request->tanggal_realisasi,
            'progres_fisik_persen'  => $request->progres_fisik, // Sesuaikan dengan nama kolom database-mu bray
            'nominal_realisasi'     => $request->nominal_realisasi,
            'keterangan'            => $request->keterangan,
            'status'                => 'pending', // ⚠️ Otomatis diturunkan ke PENDING agar di-approve ulang Kasubag
            'updated_at'            => now(),
        ];

        // 2. Jika ada upload file nota baru/susulan
        if ($request->hasFile('bukti_nota')) {
            // Hapus file nota lama di storage jika sebelumnya sudah pernah ada berkas
            if (!empty($realisasi->bukti_nota)) {
                Storage::disk('public')->delete($realisasi->bukti_nota);
            }

            // Simpan file nota baru bray
            $filePath = $request->file('bukti_nota')->store('bukti_nota', 'public');
            $updateData['bukti_nota'] = $filePath;
        }

        // 3. Eksekusi update data ke database
        DB::table('realisasi')->where('id', $id)->update($updateData);

        // 4. Catat ke Audit Trail Log Aktivitas
        DB::table('audit_logs')->insert([
            'user_id'    => auth()->id(),
            'aktivitas'  => 'EDIT_REALISASI',
            'deskripsi'  => 'User mengubah data realisasi ID ' . $id . '. Status diturunkan kembali ke PENDING.',
            'ip_address' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Data realisasi berhasil diperbarui dan dikembalikan ke status antrean antrean approval!');
    }
}