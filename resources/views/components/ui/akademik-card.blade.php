@props([
    'title',
    'value',
    'href' => null,
    'iconClass',
    'badgeText',
    'badgeColor',
    'footerText'
])

@if($href)
    <a href="{{ $href }}" class="block group h-full cursor-pointer">
        @else
            <div class="block h-full">
                @endif

                <div
                    class="bg-white rounded-lg border border-gray-200 p-5 shadow-sm h-full flex flex-col justify-between hover:shadow-md transition-shadow duration-200">

                    <div class="flex justify-between items-start">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            {{ $title }}
                        </h3>
                        <div class="text-gray-400 group-hover:text-gray-600 transition-colors">
                            <i class="{{ $iconClass }} text-lg"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h2 class="text-3xl font-bold text-gray-800">
                            {{ is_numeric($value) ? number_format($value, 0, ',', '.') : $value }}
                        </h2>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <span
                            class="{{ $badgeColor }} text-white text-[10px] font-bold px-1.5 py-0.5 rounded leading-none">{{ $badgeText }}</span>
                        <span class="text-xs text-gray-400">{{ $footerText }}</span>
                    </div>
                </div>
            @if($href)
    </a>
    @else
        </div>
@endif
