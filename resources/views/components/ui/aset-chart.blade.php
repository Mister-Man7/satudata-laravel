@props([
    'chartData' => ['labels' => [], 'data' => [], 'total' => 0],
])

@if(!empty($chartData['labels']))
<section class="mb-8">
    <div class="bg-white rounded-[1.25rem] p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">
                <i class="fa-solid fa-chart-column text-[#4B00FF] mr-2"></i>
                Klasifikasi Barang Berdasarkan Kondisi
            </h2>
            <span class="text-sm text-gray-500 font-medium">
                Total: {{ $chartData['total'] }} barang
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Chart Bar --}}
            <div class="flex items-center justify-center" style="height: 300px;">
                <canvas data-kondisi-bar-chart data-payload='@json($chartData)'></canvas>
            </div>

            {{-- Legend / Detail --}}
            <div class="flex flex-col justify-center">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Detail Kondisi</h3>
                <div class="space-y-3">
                    @foreach($chartData['labels'] as $index => $label)
                        @php
                            $total = $chartData['data'][$index] ?? 0;
                            $percent = $chartData['total'] > 0
                                ? round(($total / $chartData['total']) * 100, 1)
                                : 0;
                        @endphp
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ ['#30A64A', '#FFA726', '#EF5350', '#42A5F5', '#AB47BC', '#78909C'][$index % 6] }};"></div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ $total }} <span class="text-gray-400 font-normal">({{ $percent }}%)</span></span>
                                </div>
                                <div class="mt-1 w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                    <div class="h-2 rounded-full transition-all duration-500"
                                         style="width: {{ $percent }}%; background-color: {{ ['#30A64A', '#FFA726', '#EF5350', '#42A5F5', '#AB47BC', '#78909C'][$index % 6] }};"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif