<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Gunakan tahun anggaran dari config agar tidak hardcoded
        $tahun_sekarang = (int) config('app.tahun_anggaran', date('Y'));

        // 1. Metrik Utama (Pagu, Realisasi, Sisa)
        $total_pagu = DB::table('kegiatan')->sum('pagu_anggaran');
        $total_realisasi = DB::table('realisasi')
            ->where('status', 'approved')
            ->sum('nominal_realisasi');
        $sisa_anggaran = $total_pagu - $total_realisasi;
        $persentase = $total_pagu > 0 ? round(($total_realisasi / $total_pagu) * 100, 1) : 0;

        $metrics = [
            'total_pagu'       => $total_pagu,
            'total_realisasi'  => $total_realisasi,
            'sisa_anggaran'    => $sisa_anggaran,
            'persentase'       => $persentase,
        ];

        // 2. DATA GRAFIK BULANAN (KUMULATIF) — 1 query, bukan 12
        // Ambil total realisasi per bulan sekaligus
        $realisasiPerBulan = DB::table('realisasi')
            ->selectRaw('EXTRACT(MONTH FROM tanggal_realisasi)::integer AS bulan, SUM(nominal_realisasi) AS total')
            ->whereYear('tanggal_realisasi', $tahun_sekarang)
            ->where('status', 'approved')
            ->groupByRaw('EXTRACT(MONTH FROM tanggal_realisasi)::integer')
            ->pluck('total', 'bulan');

        // Bangun array kumulatif 12 bulan dari hasil query tunggal
        $chart_data = [];
        $kumulatif = 0;
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $kumulatif += ($realisasiPerBulan[$bulan] ?? 0);
            $chart_data[] = round($kumulatif / 1000000, 2);
        }

        // 3. LOGIKA EWS — 1 query untuk semua kegiatan + 1 query untuk semua realisasi (bukan N+1)
        $kegiatan_list = DB::table('kegiatan')->get();

        // Ambil total realisasi approved per kegiatan sekaligus — 1 query saja
        $realisasiPerKegiatan = DB::table('realisasi')
            ->selectRaw('kegiatan_id, SUM(nominal_realisasi) AS total')
            ->where('status', 'approved')
            ->groupBy('kegiatan_id')
            ->pluck('total', 'kegiatan_id');

        $ews_data = [];
        foreach ($kegiatan_list as $keg) {
            $realisasi_kegiatan = $realisasiPerKegiatan[$keg->id] ?? 0;
            $persen_realisasi = $keg->pagu_anggaran > 0
                ? round(($realisasi_kegiatan / $keg->pagu_anggaran) * 100)
                : 0;
            $deviasi = $persen_realisasi - $keg->target_serapan_persen;

            if ($deviasi <= -20) {
                $status = 'Kritis';
                $color = 'red';
            } elseif ($deviasi <= -5) {
                $status = 'Waspada';
                $color = 'yellow';
            } else {
                $status = 'Aman';
                $color = 'green';
            }

            $ews_data[] = [
                'kegiatan' => $keg->nama_kegiatan,
                'target'   => $keg->target_serapan_persen,
                'realisasi' => $persen_realisasi,
                'deviasi'  => $deviasi,
                'status'   => $status,
                'color'    => $color,
            ];
        }

        // Ambil rekapitulasi per program dari tabel 'programs'
        $rekapProgram = DB::table('programs')
            ->get()
            ->map(function ($program) {
                // Ambil ID kegiatan milik program ini
                $kegiatan = DB::table('kegiatan')
                    ->where('program_id', $program->id);

                $totalPagu = $kegiatan->sum('pagu_anggaran');
                $kegiatanIds = $kegiatan->pluck('id');

                // Hitung realisasi approved
                $totalRealisasi = DB::table('realisasi')
                    ->whereIn('kegiatan_id', $kegiatanIds)
                    ->where('status', 'approved')
                    ->sum('nominal_realisasi');

                $sisaAnggaran = $totalPagu - $totalRealisasi;
                $persentase   = $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 2) : 0;

                return (object) [
                    'kode_program'    => $program->kode_program,
                    'nama_program'    => $program->nama_program,
                    'total_pagu'      => $totalPagu,
                    'total_realisasi' => $totalRealisasi,
                    'sisa_anggaran'   => $sisaAnggaran,
                    'persentase'      => $persentase,
                ];
            });


        return view('dashboard', compact('metrics', 'ews_data', 'chart_data', 'rekapProgram'));
    }
}