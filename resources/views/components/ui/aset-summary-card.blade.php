@props([
    'stats' => null,
])

@if(!empty($stats))
@php
    $totalUnit = $stats['total_unit'] ?? 0;
    $nilaiPerolehan = (float)($stats['nilai_perolehan'] ?? 0);
    $kondisiBaik = $stats['kondisi_baik'] ?? 0;
    $kondisiRusakBerat = $stats['kondisi_rusak_berat'] ?? 0;
    $kondisiRusakRingan = $stats['kondisi_rusak_ringan'] ?? 0;
    $totalRusak = $kondisiRusakRingan + $kondisiRusakBerat;
    $totalKampus = $stats['total_kampus'] ?? 0;
    $totalTanpaKampus = (int) ($stats['total_tanpa_kampus'] ?? 0);
    $kampusBreakdown = $stats['kampus_breakdown'] ?? [];

    $pctBaik = $totalUnit > 0 ? round(($kondisiBaik / $totalUnit) * 100, 1) : 0;
    $pctRusak = $totalUnit > 0 ? round(($totalRusak / $totalUnit) * 100, 1) : 0;

    // Format nilai perolehan
    if ($nilaiPerolehan >= 1000000000000) {
        $nilaiFormatted = 'Rp ' . number_format($nilaiPerolehan / 1000000000000, 2, ',', '.') . ' T';
    } elseif ($nilaiPerolehan >= 1000000000) {
        $nilaiFormatted = 'Rp ' . number_format($nilaiPerolehan / 1000000000, 2, ',', '.') . ' M';
    } else {
        $nilaiFormatted = 'Rp ' . number_format($nilaiPerolehan, 0, ',', '.');
    }
    $nilaiFull = 'Rp ' . number_format($nilaiPerolehan, 0, ',', '.');
@endphp

<div class="mb-8">
    {{-- 4 Executive KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        
        {{-- Card 1: Total Unit BMN --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between group">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Unit Aset</span>
                    <h3 class="text-2xl sm:text-3xl font-bold font-mono text-gray-900 mt-2 tracking-tight">
                        {{ number_format($totalUnit, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg group-hover:bg-blue-600 group-hover:text-white transition-colors duration-200">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                <span class="inline-flex items-center gap-1.5 font-medium text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-full">
                    <i class="fa-solid fa-building text-[10px]"></i> {{ $totalKampus }} Kampus
                </span>
                @if($totalTanpaKampus > 0)
                    <span class="font-medium text-amber-600"
                          title="Aset pada data sumber belum memiliki id kampus maupun lokasi, sehingga belum masuk rincian kampus">
                        {{ number_format($totalTanpaKampus, 0, ',', '.') }} belum berlokasi
                    </span>
                @else
                    <span class="text-gray-400">Inventaris Terdata</span>
                @endif
            </div>
        </div>

        {{-- Card 2: Total Nilai Perolehan --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between group" title="{{ $nilaiFull }}">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Nilai Perolehan</span>
                    <h3 class="text-2xl sm:text-3xl font-bold font-mono text-gray-900 mt-2 tracking-tight">
                        {{ $nilaiFormatted }}
                    </h3>
                </div>
                <div class="w-11 h-11 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center text-lg group-hover:bg-violet-600 group-hover:text-white transition-colors duration-200">
                    <i class="fa-solid fa-coins"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                <span class="inline-flex items-center gap-1 font-medium text-violet-700 bg-violet-50 px-2.5 py-0.5 rounded-full">
                    <i class="fa-solid fa-shield-check text-[10px]"></i> Akumulasi BMN
                </span>
                <span class="text-gray-400">Nilai Buku Sistem</span>
            </div>
        </div>

        {{-- Card 3: Kondisi Baik --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between group">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Kondisi Baik</span>
                    <h3 class="text-2xl sm:text-3xl font-bold font-mono text-emerald-700 mt-2 tracking-tight">
                        {{ number_format($kondisiBaik, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-200">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                <span class="inline-flex items-center gap-1 font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full">
                    <i class="fa-solid fa-arrow-trend-up text-[10px]"></i> {{ $pctBaik }}%
                </span>
                <span class="text-gray-400">Siap Operasional</span>
            </div>
        </div>

        {{-- Card 4: Perlu Perhatian / Pemeliharaan --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between group">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Perlu Pemeliharaan</span>
                    <h3 class="text-2xl sm:text-3xl font-bold font-mono {{ $totalRusak > 0 ? 'text-amber-700' : 'text-gray-700' }} mt-2 tracking-tight">
                        {{ number_format($totalRusak, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="w-11 h-11 rounded-xl {{ $totalRusak > 0 ? 'bg-amber-50 text-amber-600 group-hover:bg-amber-600' : 'bg-gray-50 text-gray-400' }} flex items-center justify-center text-lg group-hover:text-white transition-colors duration-200">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                <span class="inline-flex items-center gap-1 font-medium text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-full">
                    {{ number_format($kondisiRusakRingan, 0, ',', '.') }} Ringan &bull; {{ number_format($kondisiRusakBerat, 0, ',', '.') }} Berat
                </span>
                <span class="text-gray-400">{{ $pctRusak }}% Total</span>
            </div>
        </div>

    </div>

    {{-- Main Chart Card --}}
    <div class="bg-white rounded-2xl  p-6 sm:p-8 shadow-sm hover:shadow-md transition-shadow">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-5 border-b border-gray-100">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-gray-900 tracking-tight flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                    Persebaran & Kondisi Inventaris Berdasarkan Kampus
                </h2>
                <p class="text-xs sm:text-sm text-gray-400 mt-1">
                    Komparasi unit kondisi baik, rusak ringan, dan rusak berat di seluruh lokasi kampus Untirta
                </p>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-auto">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                    Data Berdasarkan SIMANTAP
                </span>
            </div>
        </div>

        <!-- Big Chart Container -->
        <div class="relative w-full my-6" style="height: 380px;">
            <canvas data-aset-combo-chart data-payload='@json($kampusBreakdown)'></canvas>
        </div>

        <!-- Custom Legend Below Chart -->
        <div class="gap-4 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-xs sm:text-sm font-semibold text-gray-600">
            <div class="flex items-center gap-2">
                <span class="w-3.5 h-3.5 rounded-full bg-[#00e676] inline-block shadow-sm"></span>
                <span>Kondisi Baik</span>
            </div>
            @if($kondisiRusakRingan > 0)
            <div class="flex items-center gap-2">
                <span class="w-3.5 h-3.5 rounded-full bg-[#b9f6ca] inline-block shadow-sm"></span>
                <span>Rusak Ringan</span>
            </div>
            @endif
            <div class="flex items-center gap-2">
                <span class="w-3.5 h-3.5 rounded-full bg-[#ff5252] inline-block shadow-sm"></span>
                <span>Rusak Berat</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-7 h-0.5 bg-[#ffb74d] relative inline-flex items-center justify-center">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#ffb74d] border-2 border-white absolute shadow-xs"></span>
                </span>
                <span>Total Unit Aset</span>
            </div>
        </div>

    </div>
</div>
@endif
