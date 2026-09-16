@props([
    'stats' => null,
])

@if(!empty($stats))
@php
    $totalUnit = $stats['total_unit'] ?? 0;
    $kondisiBaik = $stats['kondisi_baik'] ?? 0;
    $kondisiRusakBerat = $stats['kondisi_rusak_berat'] ?? 0;
    $kondisiRusakRingan = $stats['kondisi_rusak_ringan'] ?? 0;
    $totalKampus = $stats['total_kampus'] ?? 5;
    $kampusBreakdown = $stats['kampus_breakdown'] ?? [];
@endphp

<div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-3xl border border-gray-100 p-6 sm:p-8 lg:p-9 shadow-sm hover:shadow-md transition-shadow relative">
        
        <!-- Header & Title Centered -->
        <div class="flex items-start justify-between mb-6">
            <div class="w-8"></div>
            <div class="text-center flex-1">
                <h2 class="text-xl sm:text-2xl font-extrabold text-gray-900 tracking-tight">
                    Jumlah Inventaris Berdasarkan Lokasi Kampus
                </h2>
                <p class="text-xs sm:text-sm text-gray-400 mt-1 font-medium">
                    Data diperbarui hari ini
                </p>
            </div>
            <button type="button" class="text-gray-500 hover:text-gray-800 p-1.5 rounded-lg hover:bg-gray-100 transition-colors" title="Menu Grafik">
                <i class="fa-solid fa-bars text-lg"></i>
            </button>
        </div>

        <!-- Big Chart Container -->
        <div class="relative w-full my-4" style="height: 420px;">
            <canvas data-aset-combo-chart data-payload='@json($kampusBreakdown)'></canvas>
        </div>

        <!-- Custom Legend Centered Below Chart -->
        <div class="mt-6 flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-xs sm:text-sm font-semibold text-gray-700">
            <div class="flex items-center gap-2">
                <span class="w-3.5 h-3.5 rounded-full bg-[#00e676] inline-block"></span>
                <span>Kondisi Baik</span>
            </div>
            @if($kondisiRusakRingan > 0)
            <div class="flex items-center gap-2">
                <span class="w-3.5 h-3.5 rounded-full bg-[#b9f6ca] inline-block"></span>
                <span>Rusak Ringan</span>
            </div>
            @endif
            <div class="flex items-center gap-2">
                <span class="w-3.5 h-3.5 rounded-full bg-[#ff5252] inline-block"></span>
                <span>Rusak Berat</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-7 h-0.5 bg-[#ffb74d] relative inline-flex items-center justify-center">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#ffb74d] border-2 border-white absolute"></span>
                </span>
                <span>Total Unit Aset</span>
            </div>
        </div>

    </div>
</div>
@endif
