@props([
    'chartData' => ['labels' => [], 'data' => [], 'total' => 0],
    'title' => 'Statistik Pegawai',
    'colors' => ['#8B5CF6', '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#EC4899', '#6366F1', '#14B8A6', '#F97316'],
    'type' => 'bar',
    'height' => 360,
    'standalone' => true,
])

@if(!empty($chartData['labels']) && count($chartData['data']) > 0)
    @php
        $chartId = 'pegawaiChart-' . md5($title . $type);
        $showLegend = in_array($type, ['pie', 'doughnut', 'polarArea', 'radar']);
        $isCartesian = in_array($type, ['bar', 'line']);
        $isCircular = in_array($type, ['pie', 'doughnut', 'polarArea']);

        $icon = match ($type) {
            'bar' => 'fa-chart-column',
            'line' => 'fa-chart-line',
            'scatter', 'bubble' => 'fa-chart-scatter',
            default => 'fa-chart-pie',
        };

        $colorValues = array_values($colors);
        $labelKeys = array_keys($chartData['labels']);

        $dataset = [
            'label' => $title,
            'data' => array_values($chartData['data']),
        ];

        if ($type === 'line') {
            $dataset['backgroundColor'] = $colorValues[0] . '22';
            $dataset['borderColor'] = $colorValues[0];
            $dataset['borderWidth'] = 3;
            $dataset['tension'] = 0.4;
            $dataset['fill'] = true;
            $dataset['pointRadius'] = 5;
            $dataset['pointHoverRadius'] = 7;
            $dataset['pointBackgroundColor'] = '#ffffff';
            $dataset['pointBorderColor'] = $colorValues[0];
            $dataset['pointBorderWidth'] = 2;
        } elseif ($type === 'radar') {
            $dataset['backgroundColor'] = $colorValues[0] . '55';
            $dataset['borderColor'] = $colorValues[0];
            $dataset['borderWidth'] = 2;
            $dataset['pointRadius'] = 4;
            $dataset['pointBackgroundColor'] = $colorValues[0];
            $dataset['pointBorderColor'] = '#ffffff';
            $dataset['pointBorderWidth'] = 2;
        } else {
            $perLabelColors = array_map(
                static fn(int $key): string => $colorValues[$key % count($colorValues)],
                $labelKeys
            );

            $dataset['backgroundColor'] = $perLabelColors;
            $dataset['borderColor'] = $perLabelColors;
            $dataset['borderWidth'] = 2;

            if ($type === 'bar') {
                $dataset['borderRadius'] = 8;
                $dataset['barThickness'] = 40;
            }

            if ($isCircular) {
                $dataset['borderColor'] = '#ffffff';
                $dataset['borderWidth'] = 2;
                $dataset['hoverOffset'] = 8;
            }
        }
    @endphp

    @if($standalone)
    <section class="mb-8">
    @endif
        <div class="bg-white rounded-[1.25rem] p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900">
                    <i class="fa-solid {{ $icon }} text-[#4B00FF] mr-2"></i>
                    {{ $title }}
                </h2>
                <span class="text-sm text-gray-500 font-medium">
                    Total: {{ number_format($chartData['total'], 0, ',', '.') }} orang
                </span>
            </div>

            <div class="relative" style="height: {{ $height }}px;">
                <canvas id="{{ $chartId }}"></canvas>
            </div>
        </div>
    @if($standalone)
    </section>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const ctx = document.getElementById('{{ $chartId }}');
                if (!ctx) return;

                const labels = @json(array_values($chartData['labels']));
                const dataset = @json($dataset);
                const total = dataset.data.reduce((a, b) => a + b, 0);

                new Chart(ctx, {
                    type: '{{ $type }}',
                    data: {
                        labels: labels,
                        datasets: [dataset]
                    },
                    plugins: [{
                        id: 'valueLabels',
                        afterDatasetsDraw: function (chart) {
                            const isCartesian = {{ $isCartesian ? 'true' : 'false' }};
                            const isCircular = {{ $isCircular ? 'true' : 'false' }};
                            const meta = chart.getDatasetMeta(0);
                            if (!meta || !meta.data || !meta.data.length) return;

                            const ctx2 = chart.ctx;
                            ctx2.save();

                            meta.data.forEach(function (element, index) {
                                const value = dataset.data[index];
                                if (value === 0 || value == null) return;

                                if (isCartesian) {
                                    ctx2.fillStyle = '#111827';
                                    ctx2.font = '600 11px sans-serif';
                                    ctx2.textAlign = 'center';
                                    ctx2.textBaseline = 'bottom';
                                    ctx2.fillText(value, element.x, element.y - 6);
                                } else if (isCircular) {
                                    const midAngle = (element.startAngle + element.endAngle) / 2;
                                    const outerRadius = element.outerRadius || 0;
                                    const startR = outerRadius + 8;
                                    const endR = outerRadius + 30;

                                    const x1 = element.x + Math.cos(midAngle) * startR;
                                    const y1 = element.y + Math.sin(midAngle) * startR;
                                    const x2 = element.x + Math.cos(midAngle) * endR;
                                    const y2 = element.y + Math.sin(midAngle) * endR;

                                    ctx2.strokeStyle = '#d1d5db';
                                    ctx2.lineWidth = 1;
                                    ctx2.beginPath();
                                    ctx2.moveTo(x1, y1);
                                    ctx2.lineTo(x2, y2);
                                    ctx2.stroke();

                                    ctx2.fillStyle = '#374151';
                                    ctx2.font = '500 11px sans-serif';
                                    ctx2.textAlign = midAngle > Math.PI / 2 && midAngle < Math.PI * 1.5 ? 'right' : 'left';
                                    ctx2.textBaseline = 'middle';
                                    ctx2.fillText(labels[index] + ': ' + value + ' %', x2 + (ctx2.textAlign === 'left' ? 4 : -4), y2);
                                }
                            });

                            ctx2.restore();
                        }
                    }],
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: {{ $showLegend ? 'true' : 'false' }},
                                position: 'bottom',
                                labels: {
                                    padding: 16,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    boxWidth: 8,
                                    font: { size: 12 },
                                    color: '#374151'
                                }
                            },
                            tooltip: {
                                backgroundColor: '#1f2937',
                                titleFont: { size: 13, weight: '600' },
                                bodyFont: { size: 12 },
                                padding: 12,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function (context) {
                                        const value = context.raw;
                                        const percent = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        const isCartesian = {{ $isCartesian ? 'true' : 'false' }};
                                        return ' ' + value + ' orang' + (isCartesian ? '' : ' (' + percent + '%)');
                                    }
                                }
                            }
                        },
                        @if($isCartesian)
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
                        @endif
                    }
                });
            });
        </script>
    @endpush
@endif