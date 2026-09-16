<x-layout>
    <x-slot:title>
        {{ $title ?? 'Pegawai' }}
    </x-slot:title>

    @php
        $daftarStatistik = $daftarStatistik ?? [];
        $statusPegawai = $statusPegawai ?? [];
    @endphp

    <section class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="font-bold text-2xl text-gray-800">
                    Ringkasan Data Kepegawaian
                </h1>
                <p class="text-xs text-gray-500 mt-0.5">
                    Sumber: SIMPEG UNTIRTA
                </p>
            </div>
        </div>

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

    <section class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 flex flex-col">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Persebaran Status Kerja Pegawai</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Persebaran Pegawai berdasarkan Status Kerja Pegawai</p>
                </div>
            </div>
            <div class="relative w-full flex-1 min-h-[320px]">
                <x-ui.pegawai-chart :chartData="$chartStatusKerja ?? ['labels' => [], 'data' => [], 'total' => 0]" title="Persebaran Status Kerja" type="bar" :standalone="false" :show-header="false" />
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8 mb-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-6 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 flex flex-col">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Status Kepegawaian SDM</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Ringkasan status keaktifan seluruh SDM</p>
                    </div>
                </div>
                <div class="relative w-full flex-1 min-h-[320px]">
                    <x-ui.pegawai-chart :chartData="$chartStatusPegawai ?? ['labels' => [], 'data' => [], 'total' => 0]" title="Status Kepegawaian" type="doughnut" :standalone="false" :show-header="false" />
                </div>
            </div>

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
        </div>
    </section>
</x-layout>