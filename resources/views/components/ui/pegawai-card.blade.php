@props([
    'title' => 'Judul Statistik',
    'value' => null,
    'href' => null,
    'cardBg' => 'bg-indigo-600', // Default diubah menjadi warna gelap agar sesuai dengan teks putih
    'textColor' => 'text-white', // Ditambahkan agar warna teks bisa menyesuaikan latar
    'iconBg' => 'bg-white/20',
    'iconColor' => 'text-white',
    'iconClass' => 'fa-solid fa-clock',
])

<div
    {{ $attributes->merge(['class' => "group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-transparent $cardBg p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"]) }}>
    <div
        class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white opacity-10 transition-transform duration-500 group-hover:scale-150">
    </div>
    <div class="relative z-10 flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium {{ $textColor }} opacity-80">
                {{ $title }}
            </p>
            <h3 class="mt-2 text-3xl font-extrabold tracking-tight {{ $textColor }}">
                @if ($value === null)
                    —
                @else
                    {{ number_format($value, 0, ',', '.') }}
                @endif
            </h3>
        </div>
        <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $iconBg }} {{ $iconColor }} shadow-inner">
            @isset($icon)
                {{ $icon }}
            @else
                <i class="{{ $iconClass }} text-xl" aria-hidden="true"></i>
            @endisset
        </div>
    </div>
    @if ($href)
        <div class="relative z-10 mt-5 border-t border-white border-opacity-20 pt-4">
            <a href="{{ $href }}"
                class="before:absolute before:inset-0 inline-flex w-full items-center justify-between text-sm font-bold {{ $textColor }} opacity-80 transition-opacity hover:opacity-100">
                <span>Lihat detail</span>
                <span class="transition-transform duration-300 group-hover:translate-x-1"
                    aria-hidden="true">&rarr;</span>
            </a>
        </div>
    @endif
</div>
