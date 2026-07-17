<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Metrik Utama (Pagu, Realisasi, Sisa)
        $total_pagu = DB::table('kegiatan')->sum('pagu_anggaran');
        $total_realisasi = DB::table('realisasi')
            ->where('status', 'approved')
            ->sum('nominal_realisasi');
        $sisa_anggaran = $total_pagu - $total_realisasi;
        $persentase = $total_pagu > 0 ? round(($total_realisasi / $total_pagu) * 100, 1) : 0;

        $metrics = [
            'total_pagu' => $total_pagu,
            'total_realisasi' => $total_realisasi,
            'sisa_anggaran' => $sisa_anggaran,
            'persentase' => $persentase
        ];

        // 2. QUERY DATA GRAFIK BULANAN (KUMULATIF)
        $tahun_sekarang = 2026; // Sesuai tahun anggaran SIMONA
        $chart_data = [];
        $kumulatif = 0;

        // Loop dari bulan 1 (Jan) sampai 12 (Des)
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            // Hitung total realisasi pada bulan tersebut
            $total_bulan_ini = DB::table('realisasi')
                ->whereYear('tanggal_realisasi', $tahun_sekarang)
                ->whereMonth('tanggal_realisasi', $bulan)
                ->where('status', 'approved')
                ->sum('nominal_realisasi');

            // Karena grafiknya KUMULATIF, kita tambahkan terus ke bulan berikutnya
            $kumulatif += $total_bulan_ini;
            
            // Konversi ke satuan "Juta" agar skala grafik di chartjs lebih rapi dan enak dilihat
            $chart_data[] = round($kumulatif / 1000000, 2); 
        }

        // 3. LOGIKA EWS (Sama seperti sebelumnya)
        $kegiatan_list = DB::table('kegiatan')->get();
        $ews_data = [];

        foreach ($kegiatan_list as $keg) {
            $realisasi_kegiatan = DB::table('realisasi')
                ->where('kegiatan_id', $keg->id)
                ->where('status', 'approved')
                ->sum('nominal_realisasi');
            $persen_realisasi = $keg->pagu_anggaran > 0 ? round(($realisasi_kegiatan / $keg->pagu_anggaran) * 100) : 0;
            $deviasi = $persen_realisasi - $keg->target_serapan_persen;

            if ($deviasi <= -20) { $status = 'Kritis'; $color = 'red'; }
            elseif ($deviasi <= -5) { $status = 'Waspada'; $color = 'yellow'; }
            else { $status = 'Aman'; $color = 'green'; }

            $ews_data[] = [
                'kegiatan' => $keg->nama_kegiatan,
                'target' => $keg->target_serapan_persen,
                'realisasi' => $persen_realisasi,
                'deviasi' => $deviasi,
                'status' => $status,
                'color' => $color
            ];
        }

        // Kirim $chart_data ke view blade
        return view('dashboard', compact('metrics', 'ews_data', 'chart_data'));
    }
}