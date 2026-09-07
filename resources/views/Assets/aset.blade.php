<x-layout>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>
    
    @if(!empty($warning))
        <div class="mb-6 px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-lg"></i>
            <div class="flex-1">
                <p class="font-medium text-sm">{{ $warning }}</p>
            </div>
            <a href="{{ url()->current() }}" class="text-sm font-semibold text-amber-700 hover:text-amber-900 underline">
                Refresh
            </a>
        </div>
    @endif

    @if(($level ?? 'kampus') === 'kampus' && !empty($summaryStats))
        <x-ui.aset-summary-card :stats="$summaryStats" />
    @endif

    <x-ui.aset-card :datas="$datas" :level="$level ?? 'bmn'"></x-ui.aset-card>
</x-layout>
