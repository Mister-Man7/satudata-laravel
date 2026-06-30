@props([
    'title' => 'Judul Statistik',
    'value' => null,
    'href' => null,
    'cardBg' => 'bg-white',
    'iconBg' => 'bg-blue-50',
    'iconColor' => 'text-blue-600',
    'iconClass' => 'fa-solid fa-clock',
    'description' => null,
    'status' => null,
])

<div
    {{ $attributes->merge(['class' => 'group rounded-2xl border border-gray-200 ' . $cardBg . ' p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-md']) }}>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-white/50">
                {{ $title }}
            </p>

            <h3 class="mt-3 text-3xl font-extrabold tracking-tight text-white">
                @if ($value === null)
                    —
                @else
                    {{ number_format($value, 0, ',', '.') }}
                @endif
            </h3>

            @if ($description)
                <p class="mt-3 text-sm leading-6 text-white/75">
                    {{ $description }}
                </p>
            @endif
        </div>

        <div
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $iconBg }} {{ $iconColor }}">
            @isset($icon)
                {{ $icon }}
            @else
                <i class="{{ $iconClass }} text-lg" aria-hidden="true"></i>
            @endisset
        </div>
    </div>

    @if ($href)
        <a href="{{ $href }}"
            class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-white/50 hover:text-white">
            Lihat detail <span aria-hidden="true">→</span>
        </a>
    @endif

    @if ($status)
        <div class="mt-5 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
            {{ $status }}
        </div>
    @endif
</div>
