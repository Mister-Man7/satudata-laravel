<div wire:init="loadData" wire:key="statistik-mahasiswa-{{ $selectedYear }}-{{ $selectedSemester }}-{{ $selectedFaculty }}" class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    {{-- Loading Skeleton / Shimmer --}}
    <div wire:loading.class.remove="hidden" wire:target="loadData" class="shimmer-card">
        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 flex flex-col gap-3 lg:col-span-7 lg:justify-center">
                <div>
                    <div class="shimmer shimmer-text-lg"></div>
                    <div class="shimmer shimmer-text-sm"></div>
                </div>
            </div>
            <div class="col-span-12 grid grid-cols-1 gap-3 sm:grid-cols-3 lg:col-span-5 lg:items-end">
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tahun Akademik</span>
                    <div class="shimmer shimmer-select"></div>
                </label>
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Semester</span>
                    <div class="shimmer shimmer-select"></div>
                </label>
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Fakultas</span>
                    <div class="shimmer shimmer-select"></div>
                </label>
            </div>
            <div class="col-span-12 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 lg:p-6">
                <div class="mb-5 flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex gap-4">
                        <div class="shimmer shimmer-summary w-32"></div>
                        <div class="shimmer shimmer-summary w-32"></div>
                        <div class="shimmer shimmer-summary w-32"></div>
                        <div class="shimmer shimmer-summary w-32"></div>
                    </div>
                </div>
                <div class="shimmer shimmer-chart"></div>
            </div>
        </div>
    </div>

    {{-- Actual Content (loaded after loadData completes) --}}
    <div wire:loading.class="hidden" wire:target="loadData" class="hidden">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 sm:p-6 lg:p-8" data-statistik-mahasiswa-root data-payload='@json($chartPayload)'>
            <div class="grid grid-cols-12 gap-6">
                @if ($toastMessage)
                    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-transition:leave="transition ease-in duration-200 opacity-0 scale-95"
                        class="col-span-12 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-950 shadow-sm">
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                            <span class="text-xs font-bold sm:text-sm">{{ $toastMessage }}</span>
                        </div>
                        <button @click="show = false" class="text-emerald-500 hover:text-emerald-700">
                            <i class="fa-solid fa-xmark text-base"></i>
                        </button>
                    </div>
                @endif

                <div class="col-span-12 flex flex-col gap-3 lg:col-span-7 lg:justify-center">
                    <div>
                        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                            {{ $title }}
                        </h2>
                        @if(!empty($subtitle))
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $subtitle }}
                            </p>
                        @endif
                    </div>
                </div>
                <div class="col-span-12 grid grid-cols-1 gap-3 sm:grid-cols-3 lg:col-span-5 lg:items-end">
                    <label class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tahun Akademik</span>
                        <select wire:model.live="selectedYear" wire:key="statistik-mahasiswa-year"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-blue-custom-500 focus:bg-white focus:ring-4 focus:ring-blue-custom-500/10">
                            @foreach ($academicYears as $year)
                                <option value="{{ $year }}" {{ $year == $selectedYear ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Semester</span>
                        <select wire:model.live="selectedSemester" wire:key="statistik-mahasiswa-semester"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-blue-custom-500 focus:bg-white focus:ring-4 focus:ring-blue-custom-500/10">
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester }}" {{ $semester == $selectedSemester ? 'selected' : '' }}>
                                    {{ $semester }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Fakultas</span>
                        <select wire:model.live="selectedFaculty" wire:key="statistik-mahasiswa-faculty"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-blue-custom-500 focus:bg-white focus:ring-4 focus:ring-blue-custom-500/10">
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty }}" {{ $faculty == $selectedFaculty ? 'selected' : '' }}>
                                    {{ $faculty }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="col-span-12 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 lg:p-6">
                    <div class="mb-5 flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-end sm:justify-between">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach ($summaryCards as $card)
                                <div class="rounded-lg p-3 {{ $card['accentBg'] }}">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] {{ $card['accentText'] }}">{{ $card['label'] }}</p>
                                    <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $card['value'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="h-[420px] w-full">
                        <canvas data-statistik-mahasiswa-chart class="h-full w-full"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    Livewire.on('statistik-mahasiswa-filter-changed', () => { });

    const initChart = () => {
        const root = document.querySelector('[data-statistik-mahasiswa-root]');
        if (root && window.initStatistikMahasiswaCharts) {
            window.initStatistikMahasiswaCharts(window.Chart);
        }
    };

    const observer = new MutationObserver(() => {
        if (document.querySelector('[data-statistik-mahasiswa-root]')) {
            initChart();
            observer.disconnect();
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
    initChart();
});
</script>
@endpush
