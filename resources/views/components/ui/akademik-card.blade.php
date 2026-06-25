@props([
    'title' => 'Judul Statistik',
    'value' => null,
    'description' => 'Deskripsi statistik.',
    'status' => null,
    'href' => null,
    'iconBg' => 'bg-blue-50',
    'iconColor' => 'text-blue-600',
])

<div
    {{ $attributes->merge(['class' => 'group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-md']) }}
>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-500">
                {{ $title }}
            </p>

            <h3 class="mt-3 text-3xl font-extrabold tracking-tight text-gray-900">
                @if ($value === null)
                    —
                @else
                    {{ number_format($value, 0, ',', '.') }}
                @endif
            </h3>

            <p class="mt-2 text-sm leading-5 text-slate-500">
                {{ $description }}
            </p>

            @if ($status)
                <p class="mt-3 text-xs font-semibold {{ $value === null ? 'text-amber-600' : 'text-emerald-600' }}">
                    {{ $status }}
                </p>
            @endif
        </div>

        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $iconBg }} {{ $iconColor }}">
            @isset($icon)
                {{ $icon }}
            @else
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z"/>
                </svg>
            @endisset
        </div>
    </div>

    @if ($href)
        <a href="{{ $href }}" class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-blue-600 hover:text-blue-700">
            Lihat detail <span aria-hidden="true">→</span>
        </a>
    @endif
</div>
