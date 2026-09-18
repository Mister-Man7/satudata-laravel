@props([
    'show' => 'isSwitching',
    'title' => 'Memuat data...',
])

{{-- Komponen Loading Overlay Umum dengan Spinner Berputar dan Padding Proporsional --}}
<div x-show="{{ $show }}" x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 sm:p-6"
    role="dialog" aria-modal="true" aria-label="{{ $title }}">

    <div class="relative rounded-3xl bg-white px-8 py-7 sm:px-10 sm:py-8 shadow-2xl border border-slate-100 text-center flex flex-col items-center min-w-[220px]">
        <div class="flex items-center justify-center mb-3" style="min-height: 56px;">
            <i class="fa-solid fa-circle-notch fa-spin text-5xl text-blue-600" style="font-size: 50px; animation: fa-spin 0.75s infinite linear; display: inline-block;"></i>
        </div>
        <p class="text-sm sm:text-base font-semibold text-slate-800 tracking-tight">
            {{ $title }}
        </p>
    </div>
</div>
