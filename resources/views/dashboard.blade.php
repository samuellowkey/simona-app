<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div>
                    <h2 class="text-xl font-black text-slate-900 leading-tight">
                        Sistem Monitoring Anggaran (SIMONA)
                    </h2>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        Selamat datang kembali, <span class="text-blue-600 font-bold">{{ Auth::user()->nama_lengkap }}</span>
                    </p>
                </div>
            </div>
            <div class="text-xs text-slate-500 font-bold bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                Tahun Anggaran: <span class="text-blue-600">2026</span>
            </div>
        </div>
    </x-slot>

    <head>
        <style>
            @media print {
                .no-print { display: none !important; }
                .print-area { width: 100% !important; margin: 0 !important; padding: 0 !important; }
            }
        </style>
    </head>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div class="text-sm text-slate-400 font-bold uppercase tracking-wider mb-1">Total Pagu</div>
                <div class="text-2xl font-black text-slate-800">Rp {{ number_format($metrics['total_pagu'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 border-b-4 border-b-emerald-500">
                <div class="text-sm text-slate-400 font-bold uppercase tracking-wider mb-1">Total Realisasi</div>
                <div class="text-2xl font-black text-emerald-600">Rp {{ number_format($metrics['total_realisasi'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div class="text-sm text-slate-400 font-bold uppercase tracking-wider mb-1">Sisa Anggaran</div>
                <div class="text-2xl font-black text-slate-800">Rp {{ number_format($metrics['sisa_anggaran'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="bg-blue-600 p-6 rounded-xl shadow-sm text-white shadow-blue-200 shadow-md">
                <div class="text-blue-200 text-sm font-bold uppercase tracking-wider mb-1">Persentase Serapan</div>
                <div class="text-3xl font-black">{{ $metrics['persentase'] ?? 0 }}%</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex flex-col justify-between h-[400px]">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Tren Penyerapan Kumulatif</h3>
                    <p class="text-xs text-slate-400 font-medium">Grafik realisasi berjalan</p>
                </div>
                <div class="w-full h-[300px] overflow-hidden mt-2">
                    <div id="trendChart"></div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex flex-col h-[400px]">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-slate-800">Early Warning System</h3>
                    <p class="text-xs text-slate-400 font-medium">Indikator deviasi serapan kegiatan</p>
                </div>
                
                <div class="flex-1 overflow-y-auto space-y-3 pr-1">
                    @if(isset($ews_data) && count($ews_data) > 0)
                        @foreach($ews_data as $item)
                        <div class="p-4 rounded-lg flex justify-between items-center transition-all duration-200 hover:scale-[1.01]
                            @if($item['color'] == 'red') bg-red-50 border-l-4 border-red-500
                            @elseif($item['color'] == 'yellow') bg-amber-50 border-l-4 border-amber-500
                            @else bg-emerald-50 border-l-4 border-emerald-500 @endif">
                            <div class="mr-2">
                                <div class="font-bold text-slate-800 text-sm mb-0.5 break-words line-clamp-1">{{ $item['kegiatan'] }}</div>
                                <div class="text-xs text-slate-500">Deviasi: <span class="font-bold text-slate-700">{{ $item['deviasi'] }}%</span></div>
                            </div>
                            <span class="text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider shrink-0
                                @if($item['color'] == 'red') bg-red-200 text-red-800
                                @elseif($item['color'] == 'yellow') bg-amber-200 text-amber-800
                                @else bg-emerald-200 text-emerald-800 @endif">
                                {{ $item['status'] }}
                            </span>
                        </div>
                        @endforeach
                    @else
                        <div class="h-full flex items-center justify-center">
                            <p class="text-sm text-slate-400 italic">Belum ada data deviasi anggaran saat ini.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>

    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const chartElement = document.querySelector('#trendChart');
            if (chartElement) {
                const chartData = {!! json_encode($chart_data ?? [0,0,0,0,0,0,0,0,0,0,0,0]) !!};
                
                const options = {
                    chart: {
                        type: 'area',
                        height: 280, // <-- Diatur pas ke 280 agar muat di dalam box kontainer h-[400px]
                        fontFamily: 'Plus Jakarta Sans, sans-serif',
                        toolbar: { show: false },
                        zoom: { enabled: false }
                    },
                    series: [{
                        name: 'Realisasi Kumulatif',
                        data: chartData
                    }],
                    dataLabels: { enabled: false },
                    stroke: {
                        curve: 'smooth',
                        width: 3,
                        colors: ['#2563eb']
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.4,
                            opacityTo: 0.05,
                            stops: [0, 90, 100]
                        }
                    },
                    colors: ['#2563eb'],
                    xaxis: {
                        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                        labels: {
                            style: { colors: '#64748b', fontWeight: 500 }
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    yaxis: {
                        title: { 
                            text: 'Juta Rupiah (Rp)',
                            style: { color: '#64748b', fontWeight: 600 }
                        },
                        labels: {
                            style: { colors: '#64748b' },
                            formatter: function (value) {
                                return "Rp " + value.toLocaleString('id-ID') + " M";
                            }
                        }
                    },
                    grid: {
                        borderColor: '#f1f5f9',
                        strokeDashArray: 4,
                        padding: { left: 10, right: 10, bottom: 0, top: 0 } // Optimalisasi ruang padding chart
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return "Rp " + val.toLocaleString('id-ID') + " Juta";
                            }
                        }
                    }
                };

                const chart = new ApexCharts(chartElement, options);
                chart.render();
            }
        });
    </script>
</x-app-layout>