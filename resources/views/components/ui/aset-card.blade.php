@props([
    'datas' => [],
    'level' => 'kampus'
])


<section>
    @if($level !== 'kampus')
        <div class="max-w-6xl mx-auto px-8 pt-6 -mb-4">
            <a href="javascript:history.back()"
               class="inline-flex items-center text-[#4B00FF] hover:text-violet-800 font-medium text-sm transition-colors">
                <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    @endif
</section>

<section>
    <div class="min-h-screen bg-gray-100 p-8 font-sans">
        <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            @forelse($datas as $card)
                @php
                    $url = '#';
                    if ($level === 'kampus') {
                        $url = route('aset.gedung', $card['id']);
                    } elseif ($level === 'gedung') {
                        $url = route('aset.ruangan', $card['id']);
                    } elseif ($level === 'ruangan') {
                        $url = route('aset.bmn', $card['id']);
                    }
                @endphp

                <div
                    class="bg-white rounded-[1.25rem] p-6 flex flex-col justify-between h-[320px] shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-shadow duration-300">

                    <div class="flex justify-between items-start">
                        <h3 class="text-[1.35rem] font-normal text-gray-900 line-clamp-2">
                            {{ $card['title'] }}
                        </h3>

                        <div class="text-[#f57c00]">
                            @if($card['icon'] === 'building')
                                <i class="fa-solid fa-map text-2xl"></i>
                            @elseif($card['icon'] === 'office')
                                <i class="fa-solid fa-building-user text-2xl"></i>
                            @elseif($card['icon'] === 'door')
                                <i class="fa-solid fa-door-open text-2xl"></i>
                            @elseif($card['icon'] === 'computer')
                                <i class="fa-solid fa-computer text-2xl"></i>
                            @elseif($card['icon'] === 'chair')
                                <i class="fa-solid fa-chair text-2xl"></i>
                            @elseif($card['icon'] === 'meja')
                                <i class="fa-solid fa-table text-2xl"></i>
                            @elseif($card['icon'] === 'tv')
                                <i class="fa-solid fa-tv text-2xl"></i>
                            @elseif($card['icon'] === 'laptop')
                                <i class="fa-solid fa-laptop text-2xl"></i>
                            @elseif($card['icon'] === 'car')
                                <i class="fa-solid fa-car text-2xl"></i>
                            @else
                                <div
                                    class="w-8 h-8 rounded-full bg-[#f57c00] text-white flex items-center justify-center text-xs font-medium">
                                    ic
                                </div>
                            @endif
                        </div>
                    </div>

                    {{--                    <div class="mt-auto mb-4">--}}
                    {{--                        <h2 class="{{ is_numeric($card['count']) ? 'text-[5.5rem]' : 'text-3xl' }} leading-none font-normal tracking-tight text-black line-clamp-2">--}}
                    {{--                            {{ $card['count'] }}--}}
                    {{--                        </h2>--}}
                    {{--                    </div>--}}

                    <div class="flex items-center justify-between mt-auto pt-2">
                        <span class="text-xs text-gray-600 font-medium">
                            Updated {{ \Carbon\Carbon::parse($card['updated'])->diffForHumans() }}
                        </span>

                        <a href="{{ $url }}"
                           class="inline-flex items-center gap-1.5 bg-[#4B00FF] hover:bg-violet-800 text-white text-[11px] font-medium px-4 py-1.5 rounded-full transition-colors">
                            Lihat Detail
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                      d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>

            @empty
                <div class="col-span-full flex flex-col items-center justify-center p-8 bg-white rounded-xl shadow-sm">
                    <i class="fa-solid fa-folder-open text-gray-300 text-5xl mb-3"></i>
                    <p class="text-gray-500">Tidak ada data ditemukan.</p>
                </div>
            @endforelse

        </div>
    </div>
</section>
