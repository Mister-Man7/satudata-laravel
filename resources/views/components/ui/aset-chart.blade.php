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
                <canvas id="kondisiBarChart"></canvas>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('kondisiBarChart');
        if (!ctx) return;

        const labels = @json($chartData['labels']);
        const data = @json($chartData['data']);
        const colors = ['#30A64A', '#FFA726', '#EF5350', '#42A5F5', '#AB47BC', '#78909C'];

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Barang',
                    data: data,
                    backgroundColor: labels.map((_, i) => colors[i % colors.length] + 'CC'),
                    borderColor: labels.map((_, i) => colors[i % colors.length]),
                    borderWidth: 2,
                    borderRadius: 8,
                    barThickness: 50,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleFont: { size: 13, weight: '600' },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const total = data.reduce((a, b) => a + b, 0);
                                const percent = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
                                return context.raw + ' barang (' + percent + '%)';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: { size: 12 },
                            color: '#6b7280'
                        },
                        grid: {
                            color: '#f3f4f6'
                        }
                    },
                    x: {
                        ticks: {
                            font: { size: 11 },
                            color: '#374151',
                            maxRotation: 45,
                            minRotation: 0
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
@endif