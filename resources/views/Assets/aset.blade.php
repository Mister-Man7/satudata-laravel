<x-layout>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>

    {{-- Alert Warning jika API gagal --}}
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

    {{-- Info Status Barang --}}
    @if(!empty($statusInfo))
        @if($statusInfo['type'] === 'success')
            <div class="mb-6 px-5 py-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center gap-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-emerald-800">Kondisi Inventaris</p>
                    <p class="text-sm text-emerald-700">{{ $statusInfo['message'] }}</p>
                </div>
            </div>
        @elseif($statusInfo['type'] === 'error')
            <div class="mb-6 px-5 py-4 rounded-xl bg-red-50 border border-red-200 flex items-center gap-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <i class="fa-solid fa-circle-exclamation text-red-600 text-lg"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-red-800">Perhatian: Ditemukan Kerusakan</p>
                    <p class="text-sm text-red-700">{{ $statusInfo['message'] }}</p>
                </div>
            </div>
        @elseif($statusInfo['type'] === 'warning')
            <div class="mb-6 px-5 py-4 rounded-xl bg-amber-50 border border-amber-200 flex items-center gap-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-amber-800">Informasi: Kerusakan Ringan</p>
                    <p class="text-sm text-amber-700">{{ $statusInfo['message'] }}</p>
                </div>
            </div>
        @else
            <div class="mb-6 px-5 py-4 rounded-xl bg-sky-50 border border-sky-200 flex items-center gap-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-sky-100 flex items-center justify-center">
                    <i class="fa-solid fa-circle-info text-sky-600 text-lg"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-sky-800">Ringkasan Inventaris</p>
                    <p class="text-sm text-sky-700">{{ $statusInfo['message'] }}</p>
                </div>
            </div>
        @endif
    @endif

    <x-ui.aset-card :datas="$datas" :level="$level ?? 'bmn'"></x-ui.aset-card>
</x-layout>
