<x-layout>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>

    {{-- Breadcrumb Navigation (For Gedung and Ruangan levels) --}}
    @if(($level ?? 'kampus') !== 'kampus')
        <nav class="mb-5 flex items-center gap-2 text-xs sm:text-sm font-medium text-gray-500" aria-label="Breadcrumb">
            <a href="{{ route('aset.index') }}" class="hover:text-blue-600 transition-colors flex items-center gap-1.5">
                <i class="fa-solid fa-boxes-stacked text-xs"></i>
                <span>Aset</span>
            </a>
            <i class="fa-solid fa-chevron-right text-[10px] text-gray-400"></i>
            @if(($level ?? '') === 'gedung')
                <span class="text-gray-900 font-semibold truncate">{{ $title }}</span>
            @elseif(($level ?? '') === 'ruangan')
                <a href="javascript:history.back()" class="hover:text-blue-600 transition-colors">
                    Daftar Gedung
                </a>
                <i class="fa-solid fa-chevron-right text-[10px] text-gray-400"></i>
                <span class="text-gray-900 font-semibold truncate">{{ $title }}</span>
            @endif
        </nav>
    @endif
    
    {{-- Warning Banner if API fails --}}
    @if(!empty($warning))
        <div class="mb-6 px-4 py-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-center gap-3 shadow-xs">
            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-triangle-exclamation text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-sm">{{ $warning }}</p>
            </div>
            <a href="{{ url()->current() }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-amber-200 hover:bg-amber-300 text-amber-900 transition-colors shrink-0">
                Muat Ulang
            </a>
        </div>
    @endif

    {{-- Campus Executive KPI & Breakdown Chart --}}
    @if(($level ?? 'kampus') === 'kampus' && !empty($summaryStats))
        <x-ui.aset-summary-card :stats="$summaryStats" />
    @endif

    {{-- Campus / Gedung / Ruangan Card Grid --}}
    <x-ui.aset-card :datas="$datas" :level="$level ?? 'kampus'"></x-ui.aset-card>
</x-layout>
