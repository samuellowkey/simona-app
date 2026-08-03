<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-black text-slate-900 leading-tight">
            Persetujuan Realisasi Anggaran (Approval)
        </h2>
        <p class="text-xs text-slate-500 font-medium mt-1">Daftar ajuan realisasi dari operator yang memerlukan validasi.</p>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-r-lg text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <th class="p-4">Operator</th>
                        <th class="p-4">Kegiatan</th>
                        <th class="p-4">Tanggal Realisasi</th>
                        <th class="p-4">Nominal</th>
                        <th class="p-4 text-center">Bukti Nota</th>
                        <th class="p-4 text-center">Aksi Pemutus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                    @forelse($antrean as $item)
                    <tr class="hover:bg-slate-50/50">
                        <td class="p-4 font-semibold text-slate-900">{{ $item->nama_lengkap }}</td>
                        <td class="p-4">{{ $item->nama_kegiatan }}</td>
                        <td class="p-4">{{ \Carbon\Carbon::parse($item->tanggal_realisasi)->translatedFormat('d F Y') }}</td>
                        <td class="p-4 font-bold text-blue-600">Rp {{ number_format($item->nominal_realisasi, 0, ',', '.') }}</td>
                        
                        <td class="p-4 text-center">
                            @if(!empty($item->bukti_nota))
                                <a href="{{ asset('storage/' . $item->bukti_nota) }}" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-700 rounded-md text-xs font-bold transition border border-slate-200">
                                    <i class="fa-solid fa-file-invoice-dollar text-sm"></i>
                                    Lihat Nota
                                </a>
                            @else
                                <span class="inline-flex items-center text-xs text-slate-400 font-medium italic">
                                    <i class="fa-solid fa-ban mr-1"></i> Tanpa Nota
                                </span>
                            @endif
                        </td>

                        <td class="p-4">
                            <div class="flex gap-2 justify-center items-center">
                                <!-- Tombol Approve -->
                                <form action="{{ route('realisasi.approval.process', $item->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menyetujui pengajuan ini?');" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition cursor-pointer">
                                        <i class="fa fa-check mr-1"></i> Setuju
                                    </button>
                                </form>

                                <!-- Tombol Trigger Modal Tolak -->
                                <button type="button" onclick="toggleRejectModal({{ $item->id }})" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition cursor-pointer">
                                    <i class="fa fa-times mr-1"></i> Tolak
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400 italic">Belum ada antrean berkas baru saat ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL TOLAK DIPINDAHKAN KE LUAR TABEL BIAR TIDAK TERBENTUR STACKING CONTEXT -->
    @foreach($antrean as $item)
        <div id="modal-reject-{{ $item->id }}" class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white p-6 rounded-xl border border-slate-200 w-full max-w-md shadow-xl text-left">
                <h4 class="font-black text-slate-900 text-lg mb-2">Alasan Penolakan</h4>
                <form action="{{ route('realisasi.approval.process', $item->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <textarea name="catatan_reject" placeholder="Contoh: Nota kwitansi tidak jelas..." class="w-full border-slate-300 rounded-lg text-sm p-3 focus:border-blue-500 focus:ring-blue-500 mb-4" rows="3" required></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="toggleRejectModal({{ $item->id }})" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg text-xs font-bold cursor-pointer">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold cursor-pointer">Kirim Tolak</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    @if(session('error'))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 mb-4 p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded-r-lg text-sm font-semibold">
        {{ session('error') }}
    </div>
    @endif

    @if($errors->any())
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 mb-4 p-4 bg-amber-50 border-l-4 border-amber-500 text-amber-800 rounded-r-lg text-sm font-semibold">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <script>
        function toggleRejectModal(id) {
            const modal = document.getElementById(`modal-reject-${id}`);
            if (modal) {
                modal.classList.toggle('hidden');
            }
        }
    </script>
</x-app-layout>