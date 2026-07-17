<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-black text-slate-900 leading-tight">
                    System Audit Logs (Log Aktivitas)
                </h2>
                <p class="text-xs text-slate-500 font-medium mt-1">Rekaman jejak digital audit trail pada aplikasi SIMONA</p>
            </div>
            <span class="bg-slate-100 text-slate-700 text-xs font-bold px-3 py-1.5 rounded-xl border border-slate-200">
                Total: {{ $logs->total() }} Log Tercatat
            </span>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
        
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
            <form action="{{ route('log.index') }}" method="GET" class="flex flex-col md:flex-row items-end gap-4">
                <div class="flex-1 w-full">
                    <label for="aktivitas" class="block text-xs font-bold text-slate-600 uppercase mb-1.5">Pilih Aktivitas</label>
                    <select name="aktivitas" id="aktivitas" class="w-full text-sm border-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm">
                        <option value="">-- Semua Aktivitas --</option>
                        <option value="MENGHAPUS_REALISASI" {{ request('aktivitas') == 'MENGHAPUS_REALISASI' ? 'selected' : '' }}>MENGHAPUS REALISASI</option>
                        <option value="APPROVAL_REALISASI" {{ request('aktivitas') == 'APPROVAL_REALISASI' ? 'selected' : '' }}>APPROVAL REALISASI</option>
                    </select>
                </div>

                <div class="flex-1 w-full">
                    <label for="tanggal" class="block text-xs font-bold text-slate-600 uppercase mb-1.5">Pilih Tanggal Kejadian</label>
                    <input type="date" name="tanggal" id="tanggal" value="{{ request('tanggal') }}" class="w-full text-sm border-slate-200 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm">
                </div>

                <div class="flex gap-2 w-full md:w-auto">
                    <button type="submit" class="w-full md:w-auto px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition shadow-sm cursor-pointer">
                        Terapkan
                    </button>
                    @if(request()->filled('aktivitas') || request()->filled('tanggal'))
                        <a href="{{ route('log.index') }}" class="w-full md:w-auto text-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition border border-slate-200">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <th class="p-4 w-44">Waktu Kejadian</th>
                            <th class="p-4 w-52 text-center">Aktivitas</th>
                            <th class="p-4">Deskripsi Riwayat & Aktor</th>
                            <th class="p-4 w-36 text-center">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                        @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="p-4 font-mono text-xs text-slate-500">
                                {{ \Carbon\Carbon::parse($log->created_at)->translatedFormat('d M Y | H:i:s') }} WIB
                            </td>
                            <td class="p-4 text-center">
                                @if(str_contains($log->aktivitas, 'HAPUS'))
                                    <span class="bg-rose-50 text-rose-700 border border-rose-100 px-2.5 py-1 rounded-md font-bold text-2xs tracking-wide uppercase">
                                        {{ str_replace('_', ' ', $log->aktivitas) }}
                                    </span>
                                @else
                                    <span class="bg-amber-50 text-amber-700 border border-amber-100 px-2.5 py-1 rounded-md font-bold text-2xs tracking-wide uppercase">
                                        {{ str_replace('_', ' ', $log->aktivitas) }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-4 text-slate-700 font-medium leading-relaxed">
                                {{ $log->deskripsi }}
                            </td>
                            <td class="p-4 text-center font-mono text-xs text-slate-400">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="p-12 text-center text-slate-400 italic">
                                <div class="flex flex-col items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-slate-300">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.03 0 1.9.693 2.166 1.638m-7.377 12.408-.621 3.308a3.75 3.75 0 0 1-4.041 3.012h-.08a3.75 3.75 0 0 1-3.32-3.007l-.622-3.308m13.045-3.411L21 11.25" />
                                    </svg>
                                    <span>Tidak ditemukan log audit yang cocok dengan filter bray.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($logs->hasPages())
                <div class="p-4 bg-slate-50 border-t border-slate-200">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>