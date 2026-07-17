<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center no-print">
            <div>
                <h2 class="text-xl font-black text-slate-900 leading-tight">
                    Rekapitulasi Laporan Realisasi Anggaran
                </h2>
                <p class="text-xs text-slate-500 font-medium mt-1">Data histori penyerapan dana tahun anggaran 2026</p>
            </div>
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2.5 rounded-xl shadow-sm transition flex items-center gap-2 cursor-pointer text-sm">
                <i class="fa-solid fa-print"></i> Cetak Laporan
            </button>
        </div>
    </x-slot>

    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; color: black; padding: 0; }
            .print-card { border: none !important; box-shadow: none !important; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #000 !important; }
        }
    </style>

    <div class="py-6" x-data="{ modalEdit: false, openModal: false, imgUrl: '', editData: {} }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="no-print bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
                <form action="{{ route('laporan.index') }}" method="GET" class="flex flex-wrap items-end gap-4 text-sm">
                    <div class="w-full md:w-52">
                        <label for="filter_periode" class="block text-xs font-bold text-slate-500 uppercase mb-1">Filter Periode</label>
                        <select id="filter_periode" name="periode" class="w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua Tahunan</option>
                            <option value="01" {{ request('periode') == '01' ? 'selected' : '' }}>Januari</option>
                            <option value="02" {{ request('periode') == '02' ? 'selected' : '' }}>Februari</option>
                            <option value="03" {{ request('periode') == '03' ? 'selected' : '' }}>Maret</option>
                            <option value="t1" {{ request('periode') == 't1' ? 'selected' : '' }}>Triwulan I (Jan-Mar)</option>
                            <option value="t2" {{ request('periode') == 't2' ? 'selected' : '' }}>Triwulan II (Apr-Jun)</option>
                            <option value="t3" {{ request('periode') == 't3' ? 'selected' : '' }}>Triwulan III (Jul-Sep)</option>
                            <option value="t4" {{ request('periode') == 't4' ? 'selected' : '' }}>Triwulan IV (Okt-Des)</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-xl font-bold transition text-sm cursor-pointer">
                        Terapkan Filter
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden print-card">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="p-4 text-center w-12">No</th>
                                <th class="p-4">Tanggal</th>
                                <th class="p-4">Nama Kegiatan</th>
                                <th class="p-4 text-right">Nominal Realisasi</th>
                                <th class="p-4 text-center">Fisik</th>
                                <th class="p-4 text-center no-print">Status</th>
                                <th class="p-4">Keterangan</th>
                                <th class="p-4 text-center w-28 no-print">Bukti Nota</th> 
                                <th class="p-4 text-center w-24 no-print">Aksi</th> 
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-sm font-medium text-slate-700">
                            @php $total_seluruh_realisasi = 0; @endphp
                            
                            @forelse($realisasiData as $index => $data)
                                @php $total_seluruh_realisasi += $data->nominal_realisasi; @endphp
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="p-4 text-center text-slate-400">
                                        {{ method_exists($realisasiData, 'firstItem') ? $realisasiData->firstItem() + $index : $index + 1 }}
                                    </td>
                                    <td class="p-4 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($data->tanggal_realisasi)->translatedFormat('d F Y') }}
                                    </td>
                                    <td class="p-4 font-semibold text-slate-900">
                                        <span class="text-xs font-mono block text-slate-400">{{ $data->kode_kegiatan }}</span>
                                        {{ $data->nama_kegiatan }}
                                    </td>
                                    <td class="p-4 text-right text-emerald-600 font-bold">
                                        Rp {{ number_format($data->nominal_realisasi, 0, ',', '.') }}
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full font-bold text-xs">
                                            {{ $data->progres_fisik_persen }}%
                                        </span>
                                    </td>
                                    <td class="p-4 text-center no-print">
                                        @switch(strtolower($data->status ?? 'pending'))
                                            @case('approved')
                                                <span class="bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-1 rounded-md uppercase tracking-wider">Approved</span>
                                                @break
                                            @case('rejected')
                                                <span class="bg-rose-100 text-rose-800 text-xs font-bold px-2.5 py-1 rounded-md uppercase tracking-wider">Rejected</span>
                                                @break
                                            @default
                                                <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2.5 py-1 rounded-md uppercase tracking-wider">Pending</span>
                                        @endswitch
                                    </td>
                                    <td class="p-4 text-slate-500 font-normal">
                                        {{ $data->keterangan ?? '-' }}
                                        @if(isset($data->nama_lengkap))
                                            <span class="block text-3xs text-slate-400 italic mt-0.5">Input oleh: {{ $data->nama_lengkap }}</span>
                                        @endif
                                    </td>

                                    <td class="p-4 text-center no-print">
                                        @if(!empty($data->bukti_nota))
                                            <button type="button"
                                               @click="imgUrl = '{{ asset('storage/' . $data->bukti_nota) }}'; openModal = true"
                                               class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs font-bold transition border border-blue-200 shadow-sm cursor-pointer">
                                                <i class="fa-solid fa-receipt"></i> Nota
                                            </button>
                                        @else
                                            <span class="text-2xs text-slate-400 font-medium italic">Tanpa Nota</span>
                                        @endif
                                    </td>

                                    <td class="p-4 text-center no-print">
                                        <div class="flex items-center justify-center gap-1.5 h-full">
                                            <button type="button" 
                                                    @click="modalEdit = true; editData = { 
                                                        id: '{{ $data->id }}', 
                                                        tanggal: '{{ $data->tanggal_realisasi }}', 
                                                        nominal: '{{ $data->nominal_realisasi }}', 
                                                        fisik: '{{ $data->progres_fisik_persen }}', 
                                                        keterangan: {{ json_encode($data->keterangan ?? '') }} 
                                                    }"
                                                    class="flex items-center justify-center p-2 rounded-lg text-amber-500 hover:text-amber-700 hover:bg-amber-50 transition cursor-pointer" 
                                                    title="Ubah Data">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                </svg>
                                            </button>

                                            <form action="{{ route('realisasi.destroy', $data->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data realisasi ini? Aktivitas ini akan tercatat di log sistem.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="flex items-center justify-center p-2 rounded-lg text-rose-600 hover:text-rose-800 hover:bg-rose-50 transition cursor-pointer" title="Hapus Data">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="p-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2 py-4">
                                        <i class="fa-solid fa-receipt text-4xl text-slate-300"></i>
                                        <p class="font-bold text-slate-600">Belum Ada Transaksi Realisasi</p>
                                        <p class="text-xs text-slate-400">Silahkan tambahkan transaksi realisasi.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        
                        @if($realisasiData->count() > 0)
                            <tfoot class="bg-slate-50 border-t-2 border-slate-300 text-sm font-bold text-slate-900">
                                <tr>
                                    <td colspan="3" class="p-4 text-right uppercase tracking-wider text-xs text-slate-500">Total Akumulasi Pengeluaran:</td>
                                    <td class="p-4 text-right text-lg text-emerald-700 font-black">
                                        Rp {{ number_format($total_seluruh_realisasi, 0, ',', '.') }}
                                    </td>
                                    <td colspan="6" class="p-4"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Pagination links --}}
            @if($realisasiData->hasPages())
            <div class="mt-4 flex justify-center no-print">
                {{ $realisasiData->links() }}
            </div>
            @endif

        </div>

        <div x-show="openModal" 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-black/85 backdrop-blur-sm no-print"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            style="display: none;">
            
             <div class="bg-white rounded-2xl max-w-5xl w-full p-6 shadow-2xl border border-slate-200 flex flex-col"
                style="height: 90vh;"
                @click.away="openModal = false">
                
                <div class="flex justify-between items-center border-b border-slate-150 pb-4 mb-4 shrink-0">
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-receipt text-blue-500 text-lg"></i> Pratinjau Dokumen Nota
                    </h3>
                    <button @click="openModal = false" class="text-slate-400 hover:text-slate-650 transition text-xl cursor-pointer p-1">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="flex-1 min-h-0 bg-slate-100 rounded-xl border border-slate-200 p-4 flex items-center justify-center overflow-hidden">
                    
                    <template x-if="imgUrl.toLowerCase().endsWith('.pdf')">
                        <iframe :src="imgUrl" style="width: 100%; height: 100%;" class="rounded-lg border-0"></iframe>
                    </template>
                    
                    <template x-if="!imgUrl.toLowerCase().endsWith('.pdf')">
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                            <img :src="imgUrl" 
                                alt="Nota Bukti Realisasi" 
                                style="max-width: 100%; max-height: 100%; object-fit: contain;"
                                class="rounded-lg shadow-sm" />
                        </div>
                    </template>
                    
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-150 pt-4 mt-4 shrink-0">
                    <a :href="imgUrl" download class="px-5 py-2.5 bg-slate-150 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition border border-slate-300 flex items-center gap-1.5">
                        <i class="fa-solid fa-download"></i> Unduh File
                    </a>
                    <button @click="openModal = false" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-md cursor-pointer">
                        Selesai
                    </button>
                </div>
            </div>
        </div>
        
        <div x-show="modalEdit" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm no-print"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             style="display: none;">
            
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200"
                 @click.away="modalEdit = false">
                
                <div class="flex justify-between items-center border-b border-slate-100 pb-3 mb-4">
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-pen-to-square text-amber-500"></i> Ubah Realisasi Anggaran
                    </h3>
                    <button @click="modalEdit = false" class="text-slate-400 hover:text-slate-600 transition text-lg cursor-pointer p-1">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form :action="'{{ url('realisasi/update') }}/' + editData.id" method="POST" enctype="multipart/form-data" class="space-y-4 text-left">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="edit_tanggal" class="block text-xs font-bold uppercase text-slate-500 mb-1">Tanggal Realisasi</label>
                            <input type="date" id="edit_tanggal" name="tanggal_realisasi" x-model="editData.tanggal" class="w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="edit_fisik" class="block text-xs font-bold uppercase text-slate-500 mb-1">Progres Fisik (%)</label>
                            <input type="number" id="edit_fisik" name="progres_fisik" x-model="editData.fisik" min="0" max="100" class="w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                        </div>
                    </div>

                    <div>
                        <label for="edit_nominal" class="block text-xs font-bold uppercase text-slate-500 mb-1">Nominal Realisasi (Rp)</label>
                        <input type="number" id="edit_nominal" name="nominal_realisasi" x-model="editData.nominal" class="w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>

                    <div>
                        <label for="edit_keterangan" class="block text-xs font-bold uppercase text-slate-500 mb-1">Keterangan / Uraian</label>
                        <textarea id="edit_keterangan" name="keterangan" x-model="editData.keterangan" rows="3" class="w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" required></textarea>
                    </div>

                    <div>
                        <label for="edit_nota" class="block text-xs font-bold uppercase text-slate-500 mb-1">Susulkan / Ganti Bukti Nota</label>
                        <input type="file" id="edit_nota" name="bukti_nota" accept="image/*,.pdf" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                        <p class="text-3xs text-slate-400 mt-1">*Kosongkan jika nota tidak ingin diubah atau belum ada berkas.</p>
                    </div>

                    <div class="flex justify-end items-center gap-3 border-t border-slate-150 pt-4 mt-6">
                        <button type="button" 
                                @click="modalEdit = false" 
                                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-250 text-slate-700 text-xs font-bold rounded-xl transition border border-slate-300 cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white text-xs font-bold rounded-xl transition shadow-md hover:shadow-lg cursor-pointer block border border-amber-600">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>