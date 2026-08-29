<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\Realisasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreRealisasiRequest;
use App\Http\Requests\UpdateRealisasiRequest;

class RealisasiController extends Controller
{
    /**
     * Menampilkan form input dengan data kegiatan + kalkulasi sisa pagu.
     * PERF FIX: Menggunakan subquery untuk menghindari N+1 query.
     */
    public function create()
    {
        // Satu query dengan subquery untuk sisa pagu — bukan N+1 lagi
        $kegiatan = DB::table('kegiatan')
            ->select([
                'kegiatan.*',
                DB::raw('kegiatan.pagu_anggaran - COALESCE((
                    SELECT SUM(r.nominal_realisasi)
                    FROM realisasi r
                    WHERE r.kegiatan_id = kegiatan.id
                    AND r.status != \'rejected\'
                ), 0) AS sisa_pagu'),
            ])
            ->get();

        return view('input-realisasi', compact('kegiatan'));
    }

    /**
     * Menyimpan data realisasi baru ke database.
     */
    public function store(StoreRealisasiRequest $request)
    {
        $kegiatan = DB::table('kegiatan')->where('id', $request->kegiatan_id)->first();

        $total_terpakai = DB::table('realisasi')
            ->where('kegiatan_id', $request->kegiatan_id)
            ->where('status', '!=', 'rejected')
            ->sum('nominal_realisasi');

        $sisa_pagu = $kegiatan->pagu_anggaran - $total_terpakai;

        if ($request->nominal_realisasi > $sisa_pagu) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['nominal_realisasi' => 'Nominal input melebihi sisa pagu anggaran!']);
        }

        // Upload file bukti nota jika ada
        $pathFile = null;
        if ($request->hasFile('bukti_nota')) {
            $pathFile = $request->file('bukti_nota')->store('bukti_nota', 'public');
        }

        // Cek apakah user adalah Admin (Gunakan hasRole() jika Spatie, atau cek kolom role langsung)
        $isAdmin = auth()->user()->hasRole('Admin'); // atau: auth()->user()->role === 'admin'
        
        // Set status & pesan notifikasi otomatis
        $status = $isAdmin ? 'approved' : 'pending';
        $pesanSukses = $isAdmin 
            ? 'Data realisasi berhasil disimpan dan langsung disetujui!' 
            : 'Data realisasi berhasil disimpan dan menunggu persetujuan!';

        DB::beginTransaction();
        try {
            DB::table('realisasi')->insert([
                'kegiatan_id'          => $request->kegiatan_id,
                'tanggal_realisasi'    => $request->tanggal_realisasi,
                'nominal_realisasi'    => $request->nominal_realisasi,
                'progres_fisik_persen' => $request->progres_fisik_persen,
                'keterangan'           => $request->keterangan,
                'bukti_nota'           => $pathFile,
                'user_id'              => auth()->id(),
                'status'               => $status, // Otomatis 'approved' jika Admin
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            DB::table('audit_logs')->insert([
                'user_id'    => auth()->id(),
                'aktivitas'  => 'INPUT_REALISASI',
                'deskripsi'  => 'User ' . auth()->user()->nama_lengkap . ' menginput realisasi Rp ' . number_format($request->nominal_realisasi, 0, ',', '.') . ' pada kegiatan: ' . $kegiatan->nama_kegiatan . ' (Status: ' . strtoupper($status) . ')',
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return redirect()->route('realisasi.create')->with('success', $pesanSukses);
        } catch (\Exception $e) {
                DB::rollback();
            Log::error('Gagal menyimpan realisasi: ' . $e->getMessage(), ['user_id' => auth()->id()]);
            return redirect()->back()->withErrors(['error' => 'Gagal menyimpan data. Silakan hubungi Administrator.']);
        }
    }

    /**
     * Index — diarahkan ke LaporanController (tidak ada route khusus untuk ini)
     */
    public function index(Request $request)
    {
        return app(LaporanController::class)->index($request);
    }

    /**
     * Menghapus data realisasi.
     * SECURITY FIX: Operator hanya bisa hapus data miliknya sendiri. Admin bisa hapus semua.
     */
    public function destroy($id)
    {
        $realisasi = DB::table('realisasi')->where('id', $id)->first();

        if (!$realisasi) {
            return redirect()->back()->withErrors(['error' => 'Data realisasi tidak ditemukan.']);
        }

        // SECURITY: Pastikan user hanya bisa hapus data miliknya, kecuali Admin
        if (!auth()->user()->hasRole('Admin') && $realisasi->user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus data ini.');
        }

        $kegiatan = DB::table('kegiatan')->where('id', $realisasi->kegiatan_id)->first();

        DB::beginTransaction();
        try {
            // Hapus file bukti nota dari storage jika ada
            if (!empty($realisasi->bukti_nota)) {
                Storage::disk('public')->delete($realisasi->bukti_nota);
            }

            DB::table('audit_logs')->insert([
                'user_id'    => Auth::id(),
                'aktivitas'  => 'MENGHAPUS_REALISASI',
                'deskripsi'  => 'User ' . (Auth::user()->nama_lengkap ?? Auth::user()->name) . ' menghapus data realisasi sebesar Rp ' . number_format($realisasi->nominal_realisasi, 0, ',', '.') . ' pada kegiatan: ' . ($kegiatan->nama_kegiatan ?? 'Tidak diketahui'),
                'ip_address' => request()->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('realisasi')->where('id', $id)->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Data realisasi berhasil dihapus dan aktivitas telah dicatat.');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Gagal menghapus realisasi ID ' . $id . ': ' . $e->getMessage(), ['user_id' => auth()->id()]);
            return redirect()->back()->withErrors(['error' => 'Gagal menghapus data. Silakan hubungi Administrator.']);
        }
    }

    /**
     * Menampilkan halaman antrean approval (Khusus Admin/Pimpinan).
     */
    public function approvalQueue()
    {
        $antrean = DB::table('realisasi')
            ->join('kegiatan', 'realisasi.kegiatan_id', '=', 'kegiatan.id')
            ->leftJoin('users', 'realisasi.user_id', '=', 'users.id')
            ->select(['realisasi.*', 'kegiatan.nama_kegiatan', 'users.nama_lengkap'])
            ->where('realisasi.status', 'pending')
            ->orderBy('realisasi.created_at', 'asc')
            ->get();

        return view('realisasi-approval', compact('antrean'));
    }

    /**
     * Memproses keputusan Approval atau Rejection.
     * SECURITY FIX: User tidak boleh approve/reject realisasi miliknya sendiri.
     */
    public function processApproval(Request $request, $id)
    {
        $request->validate([
            'action'        => 'required|in:approve,reject',
            'catatan_reject' => 'required_if:action,reject|nullable|string|max:500',
        ]);

        $realisasi = DB::table('realisasi')->where('id', $id)->first();
        if (!$realisasi) {
            return redirect()->back()->withErrors(['error' => 'Data tidak ditemukan.']);
        }

        // SECURITY: Approver tidak boleh approve data yang dia input sendiri
        if ($realisasi->user_id === auth()->id()) {
            return redirect()->back()->withErrors(['error' => 'Anda tidak dapat memvalidasi realisasi yang Anda input sendiri.']);
        }

        $statusBaru = $request->action === 'approve' ? 'approved' : 'rejected';

        DB::beginTransaction();
        try {
            DB::table('realisasi')->where('id', $id)->update([
                'status'        => $statusBaru,
                'catatan_reject' => $request->action === 'reject' ? $request->catatan_reject : null,
                'updated_at'    => now(),
            ]);

            $kegiatan = DB::table('kegiatan')->where('id', $realisasi->kegiatan_id)->first();
            DB::table('audit_logs')->insert([
                'user_id'    => auth()->id(),
                'aktivitas'  => 'APPROVAL_REALISASI',
                'deskripsi'  => 'User ' . auth()->user()->nama_lengkap . ' mengubah status realisasi Rp ' . number_format($realisasi->nominal_realisasi, 0, ',', '.') . ' menjadi ' . strtoupper($statusBaru) . ' pada kegiatan: ' . ($kegiatan->nama_kegiatan ?? ''),
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Status realisasi berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Gagal proses approval ID ' . $id . ': ' . $e->getMessage(), ['user_id' => auth()->id()]);
            return redirect()->back()->withErrors(['error' => 'Gagal memproses approval. Silakan hubungi Administrator.']);
        }
    }

    /**
     * Memperbarui data realisasi yang sudah ada.
     * SECURITY FIX: Operator hanya bisa update data miliknya sendiri.
     */
    public function update(UpdateRealisasiRequest $request, $id)
    {

        $realisasi = DB::table('realisasi')->where('id', $id)->first();
        if (!$realisasi) {
            return redirect()->back()->withErrors(['error' => 'Data tidak ditemukan.']);
        }

        // SECURITY: Hanya pemilik data atau Admin yang boleh edit
        if (!auth()->user()->hasRole('Admin') && $realisasi->user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah data ini.');
        }

        $updateData = [
            'tanggal_realisasi'    => $request->tanggal_realisasi,
            'progres_fisik_persen' => $request->progres_fisik,
            'nominal_realisasi'    => $request->nominal_realisasi,
            'keterangan'           => $request->keterangan,
            'status'               => 'pending', // Turunkan ke pending untuk review ulang
            'updated_at'           => now(),
        ];

        if ($request->hasFile('bukti_nota')) {
            if (!empty($realisasi->bukti_nota)) {
                Storage::disk('public')->delete($realisasi->bukti_nota);
            }
            $updateData['bukti_nota'] = $request->file('bukti_nota')->store('bukti_nota', 'public');
        }

        DB::beginTransaction();
        try {
            DB::table('realisasi')->where('id', $id)->update($updateData);

            DB::table('audit_logs')->insert([
                'user_id'    => auth()->id(),
                'aktivitas'  => 'EDIT_REALISASI',
                'deskripsi'  => 'User ' . auth()->user()->nama_lengkap . ' mengubah data realisasi ID ' . $id . '. Status diturunkan kembali ke PENDING untuk review ulang.',
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Data realisasi berhasil diperbarui dan dikembalikan ke antrean persetujuan.');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Gagal update realisasi ID ' . $id . ': ' . $e->getMessage(), ['user_id' => auth()->id()]);
            return redirect()->back()->withErrors(['error' => 'Gagal memperbarui data. Silakan hubungi Administrator.']);
        }
    }
}