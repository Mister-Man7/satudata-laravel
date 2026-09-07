@props([
    'stats' => null,
])

@if(!empty($stats))
@php
    $totalUnit = $stats['total_unit'] ?? 0;
    $kondisiBaik = $stats['kondisi_baik'] ?? 0;
    $kondisiRusakBerat = $stats['kondisi_rusak_berat'] ?? 0;
    $kondisiRusakRingan = $stats['kondisi_rusak_ringan'] ?? 0;
    $totalKampus = $stats['total_kampus'] ?? 6;

    $pctBaik = $totalUnit > 0 ? round(($kondisiBaik / $totalUnit) * 100, 1) : 0;
    $pctRusakBerat = $totalUnit > 0 ? round(($kondisiRusakBerat / $totalUnit) * 100, 1) : 0;
    $pctRusakRingan = $totalUnit > 0 ? round(($kondisiRusakRingan / $totalUnit) * 100, 1) : 0;
@endphp

<div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-3xl border border-gray-100 p-6 sm:p-8 lg:p-9 shadow-sm hover:shadow-md transition-shadow">
        
        <!-- Header Section inside Big Card -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-6 border-b border-gray-100">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">
                    Ringkasan Inventaris & Aset
                </h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1 max-w-2xl">
                    Rekapitulasi Sarana, Prasarana, dan Barang Milik Negara yang tersebar di {{ $totalKampus }} lokasi kampus Universitas Sultan Ageng Tirtayasa.
                </p>
            </div>
        </div>

        <!-- 4 Metric Cards Grid inside Big Card -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 my-6">
            
            <!-- Metric 1: Total Unit -->
            <div class="bg-gray-50/80 rounded-2xl p-5 border border-gray-100 hover:bg-gray-50 transition-colors group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Unit Aset</span>
                    <div class="w-10 h-10 rounded-xl bg-indigo-100/80 text-indigo-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-cubes-stacked text-lg"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <h3 class="text-3xl font-bold text-gray-900 tracking-tight">
                        {{ number_format($totalUnit, 0, ',', '.') }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                        <i class="fa-solid fa-boxes-stacked text-indigo-500"></i> Total Barang Terdata
                    </p>
                </div>
            </div>

            <!-- Metric 2: Lokasi Kampus -->
            <div class="bg-gray-50/80 rounded-2xl p-5 border border-gray-100 hover:bg-gray-50 transition-colors group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Lokasi Tersebar</span>
                    <div class="w-10 h-10 rounded-xl bg-sky-100/80 text-sky-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-location-dot text-lg"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <h3 class="text-3xl font-bold text-gray-900 tracking-tight">
                        {{ $totalKampus }} <span class="text-base font-medium text-gray-500">Lokasi</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                        <i class="fa-solid fa-building text-sky-500"></i> Kampus UNTIRTA
                    </p>
                </div>
            </div>

            <!-- Metric 3: Kondisi Baik -->
            <div class="bg-gray-50/80 rounded-2xl p-5 border border-gray-100 hover:bg-gray-50 transition-colors group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Kondisi Baik</span>
                    <div class="w-10 h-10 rounded-xl bg-emerald-100/80 text-emerald-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-circle-check text-lg"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-bold text-gray-900 tracking-tight">
                            {{ number_format($kondisiBaik, 0, ',', '.') }}
                        </h3>
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md">
                            {{ $pctBaik }}%
                        </span>
                    </div>
                    <p class="text-xs text-emerald-600 font-medium mt-1 flex items-center gap-1">
                        <i class="fa-solid fa-shield-halved"></i> Siap Digunakan & Operasional
                    </p>
                </div>
            </div>

            <!-- Metric 4: Rusak Berat -->
            <div class="bg-gray-50/80 rounded-2xl p-5 border border-gray-100 hover:bg-gray-50 transition-colors group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Rusak Berat</span>
                    <div class="w-10 h-10 rounded-xl bg-rose-100/80 text-rose-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-bold text-gray-900 tracking-tight">
                            {{ number_format($kondisiRusakBerat, 0, ',', '.') }}
                        </h3>
                        <span class="text-xs font-bold text-rose-700 bg-rose-100/80 px-2 py-0.5 rounded-md">
                            {{ $pctRusakBerat }}%
                        </span>
                    </div>
                    <p class="text-xs text-rose-600 font-medium mt-1 flex items-center gap-1">
                        <i class="fa-solid fa-wrench"></i> Perlu Pemeliharaan
                    </p>
                </div>
            </div>

        </div>

        <!-- Progress Bar & Condition Summary Footer inside Big Card -->
        <div class="pt-4 border-t border-gray-100 flex flex-col gap-3">
            <div class="flex items-center justify-between text-xs font-medium text-gray-600">
                <span class="flex items-center gap-2">
                    <i class="fa-solid fa-chart-pie text-indigo-500"></i> Rasio Kelayakan Inventaris
                </span>
                <span class="font-bold text-gray-800">{{ $pctBaik }}% Kondisi Baik</span>
            </div>
            
            <div class="w-full h-3 rounded-full bg-gray-100 p-0.5 border border-gray-200/60 flex overflow-hidden">
                <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $pctBaik }}%"></div>
                @if($pctRusakRingan > 0)
                <div class="h-full rounded-full bg-amber-400 transition-all duration-500" style="width: {{ $pctRusakRingan }}%"></div>
                @endif
                @if($pctRusakBerat > 0)
                <div class="h-full rounded-full bg-rose-500 transition-all duration-500" style="width: {{ $pctRusakBerat }}%"></div>
                @endif
            </div>

            <div class="flex flex-wrap items-center justify-between text-xs text-gray-500 pt-1 gap-2">
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center gap-1.5 font-medium text-gray-700">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Baik ({{ number_format($kondisiBaik, 0, ',', '.') }})
                    </span>
                    @if($kondisiRusakRingan > 0)
                    <span class="inline-flex items-center gap-1.5 font-medium text-gray-700">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> Rusak Ringan ({{ number_format($kondisiRusakRingan, 0, ',', '.') }})
                    </span>
                    @endif
                    <span class="inline-flex items-center gap-1.5 font-medium text-gray-700">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Rusak Berat ({{ number_format($kondisiRusakBerat, 0, ',', '.') }})
                    </span>
                </div>
                <span class="text-gray-400 text-[11px]">Sumber Data: SIMANTAP UNTIRTA</span>
            </div>
        </div>

    </div>
</div>
@endif
