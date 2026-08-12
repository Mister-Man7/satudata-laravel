<x-layout>
    <x-slot:title>
        {{ $title ?? 'Akademik' }}
    </x-slot:title>

    @php
        $daftarStatistik = $datas ?? [];
    @endphp

    <section class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h1 class="font-bold text-2xl text-gray-800">
                Ringkasan Data Mahasiswa
            </h1>

            @php
                $labelSemesterAktif = $daftarSemester[$kodeSemesterTampil] ?? $kodeSemesterTampil;
            @endphp
            <div class="relative inline-block" x-data="{ open: false }">
                <button @click="open = !open" @keydown.escape="open = false"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors cursor-pointer">
                    <svg class="h-4 w-4" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                    Ganti Semester
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 z-50 mt-2 w-72 origin-top-right rounded-xl bg-white shadow-lg ring-1 ring-black/5 focus:outline-none max-h-80 overflow-y-auto">
                    <div class="py-1">
                        @foreach ($daftarSemester as $kode => $label)
                            <a href="{{ route('akademik', ['semester' => $kode]) }}"
                                class="block px-4 py-2.5 text-sm transition-colors
                                    {{ $kode === $kodeSemesterTampil
                                        ? 'bg-blue-50 text-blue-700 font-semibold'
                                        : 'text-gray-700 hover:bg-gray-50' }}">
                                {{ $label }}
                                @if ($kode === $kodeSemesterTampil)
                                    <svg class="inline ml-1.5 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($daftarStatistik as $data)
                <x-ui.akademik-card :title="$data['title']" :value="$data['value']" :href="$data['href']" :icon-class="$data['iconClass']"
                    :badge-text="$data['badgeText']" :badge-color="$data['badgeColor']" :footer-text="$data['footerText']" />
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
                data-fakultas-root data-payload="{{ json_encode($fakultas) }}">
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
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 mt-6" data-peminat-root
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

            <div class="mt-3 p-5 col-12">
            </div>
            <div class="alert alert-info" role="alert">
                <i class="mdi mdi-information-outline mr-1 text-info"></i>
                Seleksi Nasional dihitung dari SNBP, SNBT, SNMPTN, dan SBMPTN
            </div>
            <div class="alert alert-info" role="alert">
                <i class="mdi mdi-information-outline mr-1 text-info"></i>
                Seleksi Mandiri dihitung dari SMPTN, SMBT, SMMPTN-BARAT, Ujian Mandiri, Seleksi
                Mandiri, dan Ujian Mandiri Bersama
            </div>
            <div class="alert alert-info" role="alert">
                <i class="mdi mdi-information-outline mr-1 text-info"></i>
                Lainnya
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-screen-2xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="font-bold mb-4 text-2xl text-gray-800">
                Dosen
            </h1>
            <span
                class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-4 py-1.5 text-sm font-semibold text-indigo-700">
                <i class="fa-solid fa-chalkboard-user"></i>
                {{ number_format($totalDosen ?? 0, 0, ',', '.') }} Dosen
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8"
                data-dosen-fakultas-root data-payload="{{ json_encode($dosenByFakultas ?? []) }}">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Persebaran Dosen Berdasarkan Fakultas</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Sumber: SIMPEG</p>
                    </div>
                    <div class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </div>
                </div>
                <div class="relative w-full h-[350px]">
                    <canvas data-dosen-fakultas-chart></canvas>
                </div>
            </div>

            <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8"
                data-dosen-status-root data-payload="{{ json_encode($dosenByStatus ?? []) }}">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Dosen Berdasarkan Status Kepegawaian</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Sumber: SIMPEG</p>
                    </div>
                    <div class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </div>
                </div>
                <div class="relative w-full h-[350px]">
                    <canvas data-dosen-status-chart></canvas>
                </div>
            </div>
        </div>
    </section>
</x-layout>
