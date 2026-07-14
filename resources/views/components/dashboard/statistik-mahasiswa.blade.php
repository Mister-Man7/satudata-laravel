<section data-statistik-mahasiswa-root data-payload='@json($chartPayload)'
         class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 sm:p-6 lg:p-8">
        <div class="grid grid-cols-12 gap-6">
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
                    <select data-statistik-mahasiswa-year
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-blue-custom-500 focus:bg-white focus:ring-4 focus:ring-blue-custom-500/10">
                        @foreach ($academicYears as $year)
                            {{-- 👇 Pasang ternary selected di sini --}}
                            <option value="{{ $year }}" {{ $year == $selectedYear ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Semester</span>
                    <select data-statistik-mahasiswa-semester
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
                    <select data-statistik-mahasiswa-faculty
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-blue-custom-500 focus:bg-white focus:ring-4 focus:ring-blue-custom-500/10">
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty }}">{{ $faculty }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="col-span-12 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 lg:p-6">
                <div
                    class="mb-5 flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                    </div>
                </div>
                <div class="h-[420px] w-full">
                    <canvas data-statistik-mahasiswa-chart class="h-full w-full"></canvas>
                </div>
            </div>

        </div>
    </div>
</section>
