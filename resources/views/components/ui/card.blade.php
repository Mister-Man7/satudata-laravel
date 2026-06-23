@props([
    'badge' => 'SATUDATA UNTIRTA',
    'title' => 'Cari Data Apa Hari Ini?',
    'description' => null,
    'action' => '#',
    'method' => 'GET',
    'placeholder' => 'Cari data...',
    'image' => null,
    'imageAlt' => 'Vector SatuData',
    'buttonText' => 'Cari',
])

<div {{ $attributes->merge(['class' => 'group overflow-hidden rounded-3xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-lg sm:p-6 md:p-8']) }}>
    <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">

        <div class="w-full flex-1 text-center lg:max-w-2xl lg:text-left">
            <div
                class="inline-flex items-center rounded-full bg-blue-50 px-4 py-2 text-xs font-bold tracking-wide text-blue-600 ring-1 ring-blue-100 sm:text-sm">{{ $badge }}
            </div>

            <h3 class="mt-5 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl lg:text-4xl">{{ $title }}</h3>

            @if ($description)
                <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-gray-500 sm:text-base lg:mx-0">{{ $description }}</p>
            @endif

            <form action="{{ $action }}" method="{{ $method }}" class="mx-auto mt-6 max-w-xl lg:mx-0">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div
                        class="flex flex-1 items-center rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 transition focus-within:border-blue-400 focus-within:bg-white focus-within:ring-4 focus-within:ring-blue-50">
                        <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="m21 21-4.35-4.35M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/>
                        </svg>

                        <input type="text" name="search" placeholder="{{ $placeholder }}"
                               value="{{ request('search') }}"
                               class="ml-3 w-full bg-transparent text-sm text-gray-700 placeholder-gray-400 outline-none">
                    </div>

                    <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100 sm:w-auto">{{ $buttonText }}
                    </button>
                </div>
            </form>

            @if ($slot->isNotEmpty())
                <div class="mt-6">{{ $slot }}
                </div>
            @endif
        </div>

        @if ($image)
            <div class="hidden justify-center lg:flex lg:w-[360px] lg:justify-end">
                <img src="{{ asset($image) }}" alt="{{ $imageAlt }}"
                     class="w-80 object-contain transition duration-300 group-hover:scale-105">
            </div>
        @endif

    </div>
</div>
