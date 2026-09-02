<x-layout>
    <x-slot:title>
        {{ $title ?? 'Pegawai' }}
    </x-slot:title>

    @php
        $daftarStatistik = $daftarStatistik ?? [];
        $statusPegawai = $statusPegawai ?? [];
    @endphp

    {{-- Section 1: Header Ringkasan & 4 Top KPI Cards --}}
    <section class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="font-bold text-2xl text-gray-800">
                    Ringkasan Data Kepegawaian
                </h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Sistem Informasi Manajemen Kepegawaian (SIMPEG) UNTIRTA
                </p>
            </div>

            <div class="inline-flex items-center gap-2 rounded-xl bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 border border-blue-100 shadow-sm">
                <i class="fa-solid fa-circle-check text-xs text-blue-600"></i>
                <span>SIMPEG: <strong>1.951 Live SDM</strong></span>
            </div>
        </div>

        {{-- 4 Top KPI Cards (Tanpa Trend, Tanpa Chip/Badge) --}}
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($daftarStatistik as $data)
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            {{ $data['title'] }}
                        </h3>
                        <div class="p-2 rounded-xl bg-gray-50 text-gray-600">
                            <i class="{{ $data['iconClass'] }} text-lg"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h2 class="text-3xl font-bold text-gray-900 tracking-tight">
                            {{ is_numeric($data['value']) ? number_format($data['value'], 0, ',', '.') : $data['value'] }}
                        </h2>
                    </div>
                    <div class="mt-3 border-t border-gray-50 pt-2 text-xs text-gray-400">
                        {{ $data['footerText'] }}
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm font-semibold text-amber-700">
                    Data kepegawaian belum siap ditampilkan.
                </div>
            @endforelse
        </div>
    </section>

    {{-- Section 2: Visualisasi Persebaran Status Kerja & Level Pegawai (Grid Layout 2-Kolom) --}}
    <section class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Left: Status Kerja Bar Chart --}}
            <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 flex flex-col">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Persebaran Status Kerja Pegawai</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Klasifikasi PNS, PPPK, Honorer, BLU, PKWT, & Outsourcing</p>
                    </div>
                </div>
                <div class="relative w-full flex-1 min-h-[320px]">
                    <x-ui.pegawai-chart :chartData="$chartStatusKerja ?? ['labels' => [], 'data' => [], 'total' => 0]" title="Persebaran Status Kerja" type="bar" :standalone="false" :show-header="false" />
                </div>
            </div>

            {{-- Right: Status Kepegawaian Ringkasan Satu Card --}}
            <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 flex flex-col">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Status Kepegawaian SDM</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Ringkasan status keaktifan seluruh SDM</p>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                        SIMPEG API
                    </span>
                </div>
                <div class="relative w-full flex-1 min-h-[300px]">
                    <x-ui.pegawai-chart :chartData="$chartStatusPegawai ?? ['labels' => [], 'data' => [], 'total' => 0]" title="Status Kepegawaian" type="doughnut" :standalone="false" :show-header="false" />
                </div>
            </div>
        </div>
    </section>

    {{-- Section 3: Level Pegawai & Rincian Status Kerja (Grid Layout 2-Kolom) --}}
    <section class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8 mb-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Left: Level Pegawai Chart --}}
            <div class="lg:col-span-6 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 flex flex-col">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Persebaran Level Pegawai</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Komposisi Dosen, Tendik, Dosen DT, & Dosen LB</p>
                    </div>
                </div>
                <div class="relative w-full flex-1 min-h-[320px]">
                    <x-ui.pegawai-chart :chartData="$chartLevelPegawai ?? ['labels' => [], 'data' => [], 'total' => 0]" title="Persebaran Level Pegawai" type="polarArea" :standalone="false" :show-header="false" />
                </div>
            </div>

            {{-- Right: Tabel Ringkasan Rincian Komposisi Status Kerja --}}
            <div class="lg:col-span-6 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 flex flex-col">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Rincian Komposisi Status Kerja</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Jumlah pegawai aktif per kategori status kerja</p>
                    </div>
                    <i class="fa-solid fa-id-badge text-indigo-500 text-lg"></i>
                </div>

                <div class="space-y-3 overflow-y-auto max-h-[320px] pr-1">
                    @forelse ($datas as $item)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50/80 border border-gray-100 hover:bg-indigo-50/40 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg {{ $item['bg'] ?? 'bg-indigo-600' }} flex items-center justify-center text-white text-sm shadow-sm">
                                    <i class="{{ $item['icon'] ?? 'fa-solid fa-user' }}"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-800">{{ $item['label'] }}</h4>
                                    <p class="text-xs text-gray-400">Pegawai UNTIRTA</p>
                                </div>
                            </div>
                            <span class="text-base font-bold text-gray-900">
                                {{ number_format($item['value'], 0, ',', '.') }} <span class="text-xs font-normal text-gray-500">Orang</span>
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 text-center py-4">Tidak ada data status kerja.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</x-layout>