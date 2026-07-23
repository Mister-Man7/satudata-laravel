<x-layout>
    <x-slot:title>
        {{ $title ?? 'Akademik' }}
    </x-slot:title>

    @php
        $daftarStatistik = $datas ?? [];
    @endphp

    <section class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="mb-4">
            <h1 class="font-bold mb-4 text-2xl text-gray-800">
                Ringkasan Data Mahasiswa
            </h1>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($daftarStatistik as $data)
                <x-ui.akademik-card
                    :title="$data['title']"
                    :value="$data['value']"
                    :href="$data['href']"
                    :icon-class="$data['iconClass']"
                    :badge-text="$data['badgeText']"
                    :badge-color="$data['badgeColor']"
                    :footer-text="$data['footerText']"
                />
            @empty
                <div
                    class="col-span-full rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm font-semibold text-amber-700">
                    Data akademik belum siap ditampilkan.
                </div>
            @endforelse
        </div>
    </section>

    <section class="mx-auto max-w-screen-2xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <div class="lg:col-span-9 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 flex flex-col"
                 data-fakultas-root
                 data-payload="{{ json_encode($fakultas) }}">

                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Persebaran Mahasiswa Berdasarkan Fakultas</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Sumber: Unit Penunjang Akademik Teknologi Informasi dan
                            Komunikasi</p>
                    </div>
                    <div class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </div>
                </div>

                {{-- Wadah Canvas Chart.js --}}
                <div class="relative w-full flex-1 min-h-[300px]">
                    <canvas data-fakultas-chart></canvas>
                </div>
            </div>

            <div
                class="lg:col-span-3 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 flex flex-col gap-8">
                <h4 class="flex items-center gap-2 text-base font-medium text-gray-900 mb-1">
                    <i class="fa-solid fa-medal text-lg text-yellow-600"></i>
                    <span>Top Rank Sarjana (S1)</span>
                </h4>

                <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Mahasiswa Terbanyak</h4>
                    <p class="text-4xl font-bold text-gray-900 tracking-tight">
                        {{ number_format($jurusanTerbanyak['jumlah_mahasiswa_aktif'] ?? 0, 0, ',', '.') }}
                    </p>
                    <p class="text-sm mt-2 text-emerald-600 font-medium flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-trend-up"></i>
                        <span class="text-gray-600 truncate" title="{{ $jurusanTerbanyak['nama_prodi'] ?? '-' }}">
                            {{ $jurusanTerbanyak['nama_prodi'] ?? '-' }}
                        </span>
                    </p>
                </div>

                <div class="border-t border-gray-100"></div>

                <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Mahasiswa Paling Sedikit</h4>
                    <p class="text-4xl font-bold text-gray-900 tracking-tight">
                        {{ number_format($jurusanSedikit['jumlah_mahasiswa_aktif'] ?? 0, 0, ',', '.') }}
                    </p>
                    <p class="text-sm mt-2 text-rose-500 font-medium flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-trend-down"></i>
                        <span class="text-gray-600 truncate" title="{{ $jurusanSedikit['nama_prodi'] ?? '-' }}">
                            {{ $jurusanSedikit['nama_prodi'] ?? '-' }}
                        </span>
                    </p>
                </div>

                <div class="border-t border-gray-100"></div>

                <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-1">Lulusan Terbanyak</h4>
                    <p class="text-4xl font-bold text-gray-900 tracking-tight">
                        {{ number_format($jurusanLulusTerbanyak['jumlah_mahasiswa_lulus'] ?? 0, 0, ',', '.') }}
                    </p>
                    <p class="text-sm mt-2 text-blue-500 font-medium flex items-center gap-1.5">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <span class="text-gray-600 truncate" title="{{ $jurusanLulusTerbanyak['nama_prodi'] ?? '-' }}">
                            {{ $jurusanLulusTerbanyak['nama_prodi'] ?? '-' }}
                        </span>
                    </p>
                </div>

            </div>

        </div>
    </section>

    <section class="mx-auto max-w-screen-2xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 mt-6"
             data-peminat-root
             data-payload="{{ json_encode($chartPeminat) }}">

            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Jumlah Peminat</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Sumber: Unit Penunjang Akademik Teknologi Informasi dan
                        Komunikasi</p>
                </div>
                <div class="text-gray-400 hover:text-gray-600 cursor-pointer">
                    <i class="fa-solid fa-bars text-lg"></i>
                </div>
            </div>

            <div class="relative w-full h-[400px]">
                <canvas data-peminat-chart></canvas>
            </div>

            <div
                class="mt-3 rounded-xl border border-blue-200/50 bg-blue-50/50 p-5 text-blue-900/50 shadow-sm">
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-0.5 text-lg text-black"></i>
                    <div>
                        <h4 class="font-bold text-blue-950">Keterangan:</h4>
                        <p class="mt-1 text-sm leading-relaxed text-blue-800">
                            <span class="block">- Seleksi Nasional = SNBP, SNBT, SNMPTN, dan SBMPTN </span>
                            <span class="block">- Seleksi Mandiri = SMPTN, SMBT, SMMPTN-BARAT, Ujian Mandiri, Seleksi Mandiri, dan Ujian Mandiri Bersama</span>
                            <span class="block">- Lainnya</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layout>
