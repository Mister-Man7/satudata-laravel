@props([
    'title' => 'Status Pegawai',
    'subtitle' => 'Ringkasan status pegawai berdasarkan status pegawai.',
    'stats' => [],
])

@php
    $total = collect($stats)->sum('value');
@endphp

<section class="rounded-[34px] bg-white p-8 shadow-lg">
    <div class="flex justify-between items-start mb-8">
        <div>
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-violet-100 flex items-center justify-center">
                    <i class="fa-solid fa-users text-violet-700 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-4xl font-bold">
                        {{ $title }}
                    </h2>
                    <p class="text-gray-500 mt-1">
                        {{ $subtitle }}
                    </p>
                </div>
            </div>
        </div>
        <div class="text-right">
            <div class="uppercase tracking-[5px] text-xs font-bold text-gray-400">TOTAL</div>
            <div class="text-3xl font-black">
                {{ number_format($total, 0, ',', '.') }}
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 auto-rows-[185px]">
        @foreach ($stats as $stat)
            @php
                $span = $stat['span'] ?? 'lg:col-span-3';
                $bg = $stat['bg'] ?? 'bg-violet-600';
                $text = $stat['text'] ?? 'text-white';
                $icon = $stat['icon'] ?? 'fa-solid fa-users';
            @endphp
            <button type="button"
                class="{{ $span }} {{ $bg }} {{ $text }} group relative overflow-hidden rounded-[28px] p-6 text-left shadow-lg transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl">
                <div class="absolute -right-10 -top-5 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                <div class="relative flex h-full flex-col justify-between">
                    <div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15">
                            <i class="{{ $icon }}"></i>
                        </div>

                        <h3 class="mt-4 text-lg font-semibold">
                            {{ $stat['label'] ?? '-' }}
                        </h3>
                    </div>
                    <div>
                        <div class="text-4xl font-black">
                            {{ number_format($stat['value'] ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="text-sm opacity-80">
                            Pegawai
                        </div>
                    </div>
                </div>
            </button>
        @endforeach
    </div>
</section>
