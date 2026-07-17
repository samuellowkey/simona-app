<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    /**
     * Menampilkan laporan realisasi dengan filter periode.
     * PERF FIX: Ditambah pagination agar tidak load semua data sekaligus.
     * BUG FIX: Filter bulan sekarang mendukung semua 12 bulan (bukan hanya 01-03).
     */
    public function index(Request $request)
    {
        $periode = $request->input('periode');

        $query = DB::table('realisasi')
            ->join('kegiatan', 'realisasi.kegiatan_id', '=', 'kegiatan.id')
            ->leftJoin('users', 'realisasi.user_id', '=', 'users.id')
            ->select([
                'realisasi.*',
                'kegiatan.nama_kegiatan',
                'kegiatan.kode_kegiatan',
                'kegiatan.pagu_anggaran',
                'users.nama_lengkap',
            ]);

        // BUG FIX: Filter bulan dari 01 sampai 12 (bukan hanya 01-03 seperti sebelumnya)
        // Validasi nilai periode untuk mencegah injeksi input tidak terduga
        $bulanValid = ['01','02','03','04','05','06','07','08','09','10','11','12'];
        $triwulanValid = ['t1','t2','t3','t4'];

        if ($periode) {
            if (in_array($periode, $bulanValid, true)) {
                // Filter bulanan — semua 12 bulan kini didukung
                $query->whereMonth('realisasi.tanggal_realisasi', $periode);
            } elseif (in_array($periode, $triwulanValid, true)) {
                // Filter per triwulan
                $ranges = [
                    't1' => ['start' => date('Y') . '-01-01', 'end' => date('Y') . '-03-31'],
                    't2' => ['start' => date('Y') . '-04-01', 'end' => date('Y') . '-06-30'],
                    't3' => ['start' => date('Y') . '-07-01', 'end' => date('Y') . '-09-30'],
                    't4' => ['start' => date('Y') . '-10-01', 'end' => date('Y') . '-12-31'],
                ];
                $query->whereBetween('realisasi.tanggal_realisasi', [
                    $ranges[$periode]['start'],
                    $ranges[$periode]['end'],
                ]);
            }
        }

        // PERF FIX: Gunakan paginate agar data tidak di-load semua sekaligus
        $realisasiData = $query
            ->orderBy('realisasi.tanggal_realisasi', 'desc')
            ->paginate(25)
            ->appends($request->query());

        return view('laporan', compact('realisasiData'));
    }

    /**
     * Menampilkan log aktivitas sistem dengan filter.
     */
    public function indexLog(Request $request)
    {
        $query = LogAktivitas::query();

        if ($request->filled('aktivitas')) {
            $query->where('aktivitas', $request->aktivitas);
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('created_at', $request->tanggal);
        }

        $logs = $query->latest()->paginate(10)->appends($request->query());

        return view('log.index', compact('logs'));
    }

    // NOTE: Method update() DIHAPUS dari sini karena merupakan duplikasi dari
    // RealisasiController::update(). Semua operasi update realisasi cukup
    // ditangani oleh RealisasiController.
}