<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\Realisasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RealisasiController extends Controller
{
    // Menampilkan form input dengan data asli dari tabel kegiatan
    public function create()
    {
        // Mengambil kegiatan beserta kalkulasi sisa pagu langsung dari database
        $kegiatan = DB::table('kegiatan')
            ->select('kegiatan.*')
            ->get()
            ->map(function ($item) {
                $terpakai = DB::table('realisasi')
                    ->where('kegiatan_id', $item->id)
                    ->where('status', '!=', 'rejected')
                    ->sum('nominal_realisasi');
                
                $item->sisa_pagu = $item->pagu_anggaran - $terpakai;
                return $item;
            });

        return view('input-realisasi', compact('kegiatan'));
    }

    // Menyimpan data input form ke tabel realisasi database
    public function store(Request $request)
    {
        $request->validate([
            'kegiatan_id' => 'required|integer',
            'tanggal_realisasi' => 'required|date',
            'nominal_realisasi' => 'required|numeric|min:1',
            'progres_fisik_persen' => 'required|integer|min:0|max:100',
            'keterangan' => 'nullable|string',
            'bukti_nota' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048', // <-- VALIDASI FILE BARU
        ]);

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

        // LOGIKA UPLOAD FILE
        $pathFile = null;
        if ($request->hasFile('bukti_nota')) {
            // Menyimpan file ke folder storage/app/public/bukti_nota
            $pathFile = $request->file('bukti_nota')->store('bukti_nota', 'public');
        }

        // Simpan ke Database
        DB::table('realisasi')->insert([
            'kegiatan_id' => $request->kegiatan_id,
            'tanggal_realisasi' => $request->tanggal_realisasi,
            'nominal_realisasi' => $request->nominal_realisasi,
            'progres_fisik_persen' => $request->progres_fisik_persen,
            'keterangan' => $request->keterangan,
            'bukti_nota' => $pathFile, // <-- SIMPAN PATH FILE DI SINI
            'user_id' => auth()->id(),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()->route('realisasi.create')->with('success', 'Data realisasi dan bukti nota berhasil disimpan, bray!');
    }

    public function index(Request $request)
    {
        // Agar sinkron, kita arahkan logic index RealisasiController ke LaporanController saja
        return app(LaporanController::class)->index($request);
    }

    /**
     * Menghapus data realisasi + Log Jejak Digital Audit Trail
     */
    public function destroy($id)
    {
        // Temukan data realisasi di tabel 'realisasi'
        $realisasi = DB::table('realisasi')->where('id', $id)->first();

        if (!$realisasi) {
            return redirect()->back()->withErrors(['error' => 'Data realisasi tidak ditemukan!']);
        }

        // Ambil info detail kegiatan untuk log audit
        $kegiatan = DB::table('kegiatan')->where('id', $realisasi->kegiatan_id)->first();

        DB::beginTransaction();
        try {
            // 1. Tulis Jejak Digital ke Audit Trail
            DB::table('audit_logs')->insert([
                'user_id' => Auth::id(),
                'aktivitas' => 'MENGHAPUS_REALISASI',
                'deskripsi' => 'User ' . (Auth::user()->nama_lengkap ?? Auth::user()->name) . ' menghapus data realisasi sebesar Rp ' . number_format($realisasi->nominal_realisasi, 0, ',', '.') . ' pada kegiatan: ' . ($kegiatan->nama_kegiatan ?? 'Tidak diketahui'),
                'ip_address' => request()->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Eksekusi Hapus Data Realisasi asli
            DB::table('realisasi')->where('id', $id)->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Data realisasi berhasil dihapus dan aktivitas telah dicatat oleh sistem audit!');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors(['error' => 'Gagal menghapus data: ' . $e->getMessage()]);
        }
    }

    /**
 * Menampilkan halaman antrean approval (Khusus Admin/Kasubag)
 */
    public function approvalQueue()
    {
        // Mengambil data realisasi yang statusnya masih pending
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
     * Memproses keputusan Approval atau Rejection
     */
    public function processApproval(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'catatan_reject' => 'required_if:action,reject|nullable|string'
        ]);

        $realisasi = DB::table('realisasi')->where('id', $id)->first();
        if (!$realisasi) {
            return redirect()->back()->withErrors(['error' => 'Data tidak ditemukan!']);
        }

        $statusBaru = $request->action === 'approve' ? 'approved' : 'rejected';

        DB::beginTransaction();
        try {
            // 1. Update status data realisasi
            DB::table('realisasi')->where('id', $id)->update([
                'status' => $statusBaru,
                'catatan_reject' => $request->action === 'reject' ? $request->catatan_reject : null,
                'updated_at' => now()
            ]);

            // 2. Catat ke Audit Trail (Jejak Digital)
            $kegiatan = DB::table('kegiatan')->where('id', $realisasi->kegiatan_id)->first();
            DB::table('audit_logs')->insert([
                'user_id' => auth()->id(),
                'aktivitas' => 'APPROVAL_REALISASI',
                'deskripsi' => 'User ' . auth()->user()->nama_lengkap . ' mengubah status realisasi Rp ' . number_format($realisasi->nominal_realisasi, 0, ',', '.') . ' menjadi ' . strtoupper($statusBaru) . ' pada kegiatan: ' . $kegiatan->nama_kegiatan,
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Status realisasi berhasil diperbarui, bray!');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors(['error' => 'Gagal memproses approval: ' . $e->getMessage()]);
        }
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