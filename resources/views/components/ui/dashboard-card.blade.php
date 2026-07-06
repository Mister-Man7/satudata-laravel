@props([
    'title' => 'Judul card',
    'description' => 'Deskripsi card',
    'badge' => 'Kategori',
    'href' => '#',
    'iconBg' => 'bg-blue-50',
    'iconColor' => 'text-blue-600',
])

<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' =>
            'group block rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md',
    ]) }}>
    <div class="flex items-start gap-4">
        <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl mb-3 {{ $iconBg }} {{ $iconColor }}">
            @isset($icon)
                {{ $icon }}
            @else
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            @endisset
        </div>
    </div>

    <div class="min-w-0 flex-1">
        <h3 class="text-base font-bold leading-6 text-gray-900 group-hover:text-blue-600">{{ $title }}
        </h3>
        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $description }}</p>
        <div class="mt-5">
            <span
                class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $badge }}</span>
        </div>
    </div>
</a>
