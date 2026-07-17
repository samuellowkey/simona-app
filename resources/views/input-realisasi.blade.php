<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-black text-slate-900 leading-tight">
            Form Input Realisasi Anggaran
        </h2>
        <p class="text-xs text-slate-500 font-medium mt-1">Catat realisasi pengeluaran bulanan secara berkala.</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-4 p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 rounded-xl font-medium text-sm">
                    <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
                </div>
            @endif

            @if($errors->has('nominal_realisasi'))
                <div class="mb-4 p-4 bg-rose-100 border-l-4 border-rose-500 text-rose-800 rounded-xl font-medium text-sm">
                    <i class="fa-solid fa-circle-exclamation mr-2"></i> {{ $errors->first('nominal_realisasi') }}
                </div>
            @endif

            @if($errors->has('bukti_nota'))
                <div class="mb-4 p-4 bg-rose-100 border-l-4 border-rose-500 text-rose-800 rounded-xl font-medium text-sm">
                    <i class="fa-solid fa-circle-exclamation mr-2"></i> {{ $errors->first('bukti_nota') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl p-6 border border-slate-200">
                <form action="{{ route('realisasi.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Pilih Kegiatan</label>
                        <select name="kegiatan_id" class="w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="">-- Pilih Kegiatan --</option>
                            @foreach($kegiatan as $keg)
                                <option value="{{ $keg->id }}" data-sisa="{{ $keg->sisa_pagu }}">
                                    {{ $keg->kode_kegiatan }} - {{ $keg->nama_kegiatan }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tanggal Realisasi</label>
                            <input type="date" name="tanggal_realisasi" value="{{ old('tanggal_realisasi', date('Y-m-d')) }}" class="w-full rounded-xl border-slate-200 text-sm" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Progres Fisik (%)</label>
                            <input type="number" name="progres_fisik_persen" value="{{ old('progres_fisik_persen', 0) }}" min="0" max="100" class="w-full rounded-xl border-slate-200 text-sm" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nominal Realisasi (Rp)</label>
                        <input type="number" name="nominal_realisasi" value="{{ old('nominal_realisasi') }}" placeholder="Contoh: 25000000" class="w-full rounded-xl border-slate-200 text-sm" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Keterangan / Uraian</label>
                        <textarea name="keterangan" rows="3" placeholder="Uraian kwitansi/nota pencairan..." class="w-full rounded-xl border-slate-200 text-sm">{{ old('keterangan') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Upload Bukti Struk / Kwitansi / Nota</label>
                        <input type="file" name="bukti_nota" 
                               class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:uppercase file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 rounded-xl p-1.5 bg-slate-50 cursor-pointer shadow-sm focus:border-blue-500 focus:ring-blue-500" accept="image/*,.pdf">
                        <p class="text-[10px] text-slate-400 mt-1 font-medium">* Format yang didukung: JPG, PNG, PDF. Ukuran maksimal file: 2 MB.</p>
                    </div>

                    <div class="pt-2">
                        <button type="submit" id="btn-submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-xl text-sm shadow-sm transition cursor-pointer">
                            Simpan Data Realisasi
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script>
        function cekSisaPagu() {
            const selectKegiatan = document.getElementById('kegiatan_id');
            const inputNominal = document.getElementById('nominal_realisasi');
            const infoPagu = document.getElementById('info-pagu');
            const errorPagu = document.getElementById('error-pagu');
            const btnSubmit = document.getElementById('btn-submit');

            // Ambil option yang sedang dipilih
            const selectedOption = selectKegiatan.options[selectKegiatan.selectedIndex];

            if (!selectedOption.value) {
                infoPagu.innerText = "";
                errorPagu.classList.add('hidden');
                return;
            }

            // Ambil nilai sisa pagu dari atribut data-sisa
            const sisaPagu = parseFloat(selectedOption.getAttribute('data-sisa')) || 0;

            // Format ke Rupiah untuk display
            const formatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
            infoPagu.innerText = "Sisa Pagu Aktif: " + formatter.format(sisaPagu);

            const nominalInput = parseFloat(inputNominal.value) || 0;

            // Validasi jika nominal input melebihi sisa pagu bray
            if (nominalInput > sisaPagu) {
                errorPagu.innerText = "⚠️ Nominal melebihi sisa pagu anggaran yang tersedia!";
                errorPagu.classList.remove('hidden');
                inputNominal.classList.add('border-rose-500', 'focus:ring-rose-500', 'focus:border-rose-500');
                btnSubmit.disabled = true;
                btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                errorPagu.classList.add('hidden');
                inputNominal.classList.remove('border-rose-500', 'focus:ring-rose-500', 'focus:border-rose-500');
                btnSubmit.disabled = false;
                btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    </script>
</x-app-layout>