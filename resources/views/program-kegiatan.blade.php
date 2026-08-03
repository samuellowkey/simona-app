<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-black text-slate-900 leading-tight">
            Daftar Program & Kegiatan
        </h2>
        <p class="text-xs text-slate-500 font-medium mt-1">Melihat rincian pagu anggaran indikatif tahun 2026</p>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Alpine State untuk Modal Manual, Excel, dan Edit -->
        <div x-data="{ 
            modalManual: false, 
            modalExcel: false, 
            modalEdit: false,
            editData: { id: '', program_id: '', kode_kegiatan: '', nama_kegiatan: '', pagu_anggaran: '', target_serapan_persen: '' }
        }">
            
            @if(session('success'))
                <div class="mb-4 p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 rounded-xl font-medium text-sm">
                    <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
                </div>
            @endif

            <!-- Area Tombol Utama -->
            <div class="flex justify-end items-center mb-6 gap-2">
                <button @click="modalExcel = true" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-sm transition flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-file-excel"></i> Import Excel
                </button>
                <button @click="modalManual = true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-sm transition flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-plus"></i> Tambah Pagu Manual
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase tracking-wider text-slate-400">
                            <th class="p-4">Kode</th>
                            <th class="p-4">Program / Kegiatan</th>
                            <th class="p-4 text-center">Target Serapan</th>
                            <th class="p-4 text-right">Pagu Anggaran</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($kegiatanData as $keg)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-4 font-mono font-bold text-slate-600">{{ $keg->kode_kegiatan }}</td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-800">{{ $keg->nama_kegiatan }}</div>
                                    <div class="text-xs text-slate-400">Program: {{ $keg->nama_program }} ({{ $keg->kode_program }})</div>
                                </td>
                                <td class="p-4 text-center font-bold text-slate-600">{{ $keg->target_serapan_persen }}%</td>
                                <td class="p-4 text-right font-bold text-slate-900">Rp {{ number_format($keg->pagu_anggaran, 0, ',', '.') }}</td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Tombol Edit -->
                                        <button @click="
                                            editData = {
                                                id: '{{ $keg->id }}',
                                                program_id: '{{ $keg->program_id }}',
                                                kode_kegiatan: '{{ $keg->kode_kegiatan }}',
                                                nama_kegiatan: '{{ addslashes($keg->nama_kegiatan) }}',
                                                pagu_anggaran: '{{ $keg->pagu_anggaran }}',
                                                target_serapan_persen: '{{ $keg->target_serapan_persen }}'
                                            };
                                            modalEdit = true;
                                        " class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Edit Pagu">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <!-- Tombol Delete -->
                                        <form action="{{ route('kegiatan.destroy', $keg->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pagu kegiatan ini?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Pagu">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <i class="fa-solid fa-folder-open text-5xl text-slate-300"></i>
                                        <div>
                                            <p class="font-bold text-slate-700 text-base">Belum Ada Data Anggaran</p>
                                            <p class="text-xs text-slate-400 mt-1">Silakan gunakan tombol di atas untuk mengisi data baru.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Modal Manual (Tambah) -->
            <div x-show="modalManual" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4" x-transition style="display: none;">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg p-6" @click.away="modalManual = false">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Form Tambah Pagu Manual</h3>
                        <button @click="modalManual = false" class="text-slate-400 hover:text-slate-600">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>
                    
                    <form action="{{ route('kegiatan.storeManual') }}" method="POST" class="space-y-4">
                        @csrf
                        
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Pilih Program Induk</label>
                            <select name="program_id" id="program_id" onchange="toggleProgramLainnya()" class="w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="">-- Pilih Program --</option>
                                @foreach($programList as $prog)
                                    <option value="{{ $prog->id }}">{{ $prog->kode_program }} - {{ $prog->nama_program }}</option>
                                @endforeach
                                <option value="OTHER" class="text-blue-600 font-bold">➕ Tambah Program Baru (Lainnya)...</option>
                            </select>
                        </div>

                        <div id="box-program-baru" class="hidden bg-blue-50/50 p-4 rounded-xl border border-dashed border-blue-200 space-y-3 transition-all duration-300">
                            <p class="text-xs font-bold text-blue-600 flex items-center gap-1">
                                <i class="fa-solid fa-circle-info"></i> Buat Program Induk Baru
                            </p>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="col-span-1">
                                    <label class="block text-2xs font-bold uppercase text-slate-500 mb-1">Kode Program</label>
                                    <input type="text" name="new_kode_program" id="new_kode_program" placeholder="PRG.04" class="w-full rounded-xl border-slate-200 text-sm placeholder:text-slate-300">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-2xs font-bold uppercase text-slate-500 mb-1">Nama Program Induk</label>
                                    <input type="text" name="new_nama_program" id="new_nama_program" placeholder="Nama program induk baru..." class="w-full rounded-xl border-slate-200 text-sm placeholder:text-slate-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-2xs font-bold uppercase text-slate-500 mb-1">Tahun Anggaran</label>
                                <input type="number" name="new_tahun_anggaran" id="new_tahun_anggaran" value="2026" class="w-full rounded-xl border-slate-200 text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-1">
                                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Kode Kegiatan</label>
                                <input type="text" name="kode_kegiatan" placeholder="01.A" class="w-full rounded-xl border-slate-200 text-sm" required>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Kegiatan</label>
                                <input type="text" name="nama_kegiatan" placeholder="Nama operasional kegiatan..." class="w-full rounded-xl border-slate-200 text-sm" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nominal Pagu (Rp)</label>
                                <input type="number" name="pagu_anggaran" placeholder="Contoh: 50000000" class="w-full rounded-xl border-slate-200 text-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Target Serapan (%)</label>
                                <input type="number" name="target_serapan_persen" placeholder="Contoh: 85" class="w-full rounded-xl border-slate-200 text-sm" required>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-xl text-sm shadow-sm transition mt-2 cursor-pointer">
                            Simpan Pagu Anggaran
                        </button>
                    </form>
                </div>
            </div>

            <!-- Modal Edit Pagu -->
            <div x-show="modalEdit" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4" x-transition style="display: none;">
                
                <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]" @click.away="modalEdit = false">
                    
                    <!-- Header -->
                    <div class="flex justify-between items-center p-6 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-800">Edit Pagu Anggaran</h3>
                        <button type="button" @click="modalEdit = false" class="text-slate-400 hover:text-slate-600 transition">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>
                    
                    <!-- Form -->
                    <form :action="'/kegiatan/' + editData.id" method="POST" class="flex flex-col flex-1 overflow-hidden">
                        @csrf
                        @method('PUT')
                        
                        <!-- Body Form dengan Spacing Pas -->
                        <div class="p-6 overflow-y-auto space-y-4">
                            
                            <!-- Program Induk -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Program Induk</label>
                                <select name="program_id" x-model="editData.program_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 text-slate-800" required>
                                    @foreach($programList as $prog)
                                        <option value="{{ $prog->id }}">{{ $prog->kode_program }} - {{ $prog->nama_program }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Kode & Nama Kegiatan dalam 2 Kolom -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="sm:col-span-1">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Kode</label>
                                    <input type="text" name="kode_kegiatan" x-model="editData.kode_kegiatan" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 text-slate-800" required>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Nama Kegiatan</label>
                                    <input type="text" name="nama_kegiatan" x-model="editData.nama_kegiatan" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 text-slate-800" required>
                                </div>
                            </div>

                            <!-- Nominal & Target dalam 2 Kolom -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Nominal Pagu (Rp)</label>
                                    <input type="number" name="pagu_anggaran" x-model="editData.pagu_anggaran" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 text-slate-800" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Target Serapan (%)</label>
                                    <input type="number" name="target_serapan_persen" x-model="editData.target_serapan_persen" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 text-slate-800" required>
                                </div>
                            </div>

                        </div>

                        <!-- Footer Action (Warna Tombol Dijamin Muncul) -->
                        <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                            <button type="button" @click="modalEdit = false" class="px-4 py-2.5 rounded-xl font-semibold text-xs text-slate-600 bg-white border border-slate-200 hover:bg-slate-100 transition">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-amber-500 hover:bg-amber-600 transition shadow-sm cursor-pointer" style="background-color: #d97706 !important; color: #ffffff !important;">
                                Update Pagu Anggaran
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Modal Excel -->
            <div x-show="modalExcel" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4" x-transition style="display: none;">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md p-6" @click.away="modalExcel = false">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Import Anggaran via Excel (CSV)</h3>
                        <button @click="modalExcel = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form action="{{ route('kegiatan.importExcel') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="p-4 bg-slate-50 rounded-xl border border-dashed border-slate-200 text-xs text-slate-500 space-y-1">
                            <p class="font-bold text-slate-700"><i class="fa-solid fa-circle-info text-blue-500"></i> Aturan Format Kolom CSV:</p>
                            <p class="font-mono">kode_program, kode_kegiatan, nama_kegiatan, pagu, target</p>
                            <p class="text-slate-400 italic mt-1">*Pastikan kode_program sudah terdaftar di database.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Pilih File CSV</label>
                            <input type="file" name="file_excel" accept=".csv" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                        </div>
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl text-sm shadow-sm transition mt-2 cursor-pointer">
                            Mulai Proses Import
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
        function toggleProgramLainnya() {
            const selectElement = document.getElementById('program_id');
            const boxProgramBaru = document.getElementById('box-program-baru');
            const newKode = document.getElementById('new_kode_program');
            const newNama = document.getElementById('new_nama_program');
            const newTahun = document.getElementById('new_tahun_anggaran');

            if (selectElement.value === 'OTHER') {
                boxProgramBaru.classList.remove('hidden');
                newKode.required = true;
                newNama.required = true;
                newTahun.required = true;
                newKode.focus();
            } else {
                boxProgramBaru.classList.add('hidden');
                newKode.required = false;
                newNama.required = false;
                newTahun.required = false;
                
                newKode.value = '';
                newNama.value = '';
            }
        }
    </script>
</x-app-layout>