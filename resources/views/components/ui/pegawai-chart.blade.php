@props([
    'chartData' => ['labels' => [], 'data' => [], 'total' => 0],
    'title' => 'Statistik Pegawai',
    'colors' => ['#8B5CF6', '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#EC4899', '#6366F1', '#14B8A6', '#F97316'],
    'type' => 'bar',
    'height' => 340,
    'standalone' => true,
    'showHeader' => true,
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
                $dataset['maxBarThickness'] = 32;
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
        <div class="bg-white rounded-[1.25rem] p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
            @if($showHeader)
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900">
                    <i class="fa-solid {{ $icon }} text-[#4B00FF] mr-2"></i>
                    {{ $title }}
                </h2>
                <span class="text-sm text-gray-500 font-medium">
                    Total: {{ number_format($chartData['total'], 0, ',', '.') }} orang
                </span>
            </div>
            @endif

            <div class="relative" style="height: {{ $height }}px;">
                <canvas id="{{ $chartId }}" data-pegawai-chart data-payload='@json($chartData)' data-type="{{ $type }}" data-title="{{ $title }}" data-show-legend="{{ $showLegend ? 'true' : 'false' }}" data-is-cartesian="{{ $isCartesian ? 'true' : 'false' }}" data-dataset='@json($dataset)'></canvas>
            </div>
        </div>
    </section>
    @else
        @if($showHeader)
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold text-gray-900">
                <i class="fa-solid {{ $icon }} text-indigo-600 mr-2"></i>
                {{ $title }}
            </h2>
            <span class="text-xs text-gray-500 font-medium">
                Total: {{ number_format($chartData['total'], 0, ',', '.') }} orang
            </span>
        </div>
        @endif

        <div class="relative w-full" style="height: {{ $height }}px;">
            <canvas id="{{ $chartId }}" data-pegawai-chart data-payload='@json($chartData)' data-type="{{ $type }}" data-title="{{ $title }}" data-show-legend="{{ $showLegend ? 'true' : 'false' }}" data-is-cartesian="{{ $isCartesian ? 'true' : 'false' }}" data-dataset='@json($dataset)'></canvas>
        </div>
    @endif
@endif