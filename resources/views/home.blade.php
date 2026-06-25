<title>{{ $title ?? 'SATUDATA UNTIRTA' }}</title>
<x-layout>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>

    <section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8 lg:py-10">
        <x-ui.card class="mt-6"
                   badge="SATUDATA UNTIRTA"
                   title="Cari Data Apa Hari Ini?"
                   description="Akses terpadu data akademik, aset, kemahasiswaan, dan infrastruktur Universitas Sultan Ageng Tirtayasa dalam satu genggaman."
                   action="#"
                   method="GET"
                   placeholder="Cari data..."
                   image="images/anim_4.svg"
                   image-alt="Vector SatuData"
                   button="Cari"
        ></x-ui.card>
    </section>
    <section class="mx-auto max-w-7xl px-4 pb-10 sm:px-6 lg:px-8">
        <h2 class="font-bold mb-7 text-2xl">
            Data Utama
        </h2>
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">

            <x-ui.dashboard-card
                title="Jumlah Mahasiswa Untirta"
                description="Status mahasiswa, gender, dan persebaran fakultas."
                badge="Akademik"
                href="/akademik"
                icon-bg="bg-indigo-50"
                icon-color="text-indigo-600"
            >
                <x-slot:icon>
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 3 2 8l10 5 10-5-10-5Zm-6 8.3V15c0 2.2 2.7 4 6 4s6-1.8 6-4v-3.7l-6 3-6-3Z"/>
                    </svg>
                </x-slot:icon>
            </x-ui.dashboard-card>

            <x-ui.dashboard-card
                title="Inventaris Sarana dan Prasarana"
                description="Ringkasan aset kampus berdasarkan kategori pemanfaatan."
                badge="Aset"
                href="/asset"
                icon-bg="bg-emerald-50"
                icon-color="text-emerald-600"
            >
                <x-slot:icon>
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M9 3a2 2 0 0 0-2 2v2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7h-5V9h-2v2h-4V9H8v2H5V9h2v1h2V5h6v2h2V5a2 2 0 0 0-2-2H9Z"/>
                    </svg>
                </x-slot:icon>
            </x-ui.dashboard-card>

            <x-ui.dashboard-card
                title="Komposisi Pegawai"
                description="Data pegawai aktif per unit kerja dan jabatan."
                badge="Pegawai"
                href="/pegawai"
                icon-bg="bg-yellow-50"
                icon-color="text-yellow-600"
            >
                <x-slot:icon>
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M8 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H8Zm4 4a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5Zm-4 10c.4-2.2 2-3.5 4-3.5s3.6 1.3 4 3.5H8Z"/>
                    </svg>
                </x-slot:icon>
            </x-ui.dashboard-card>

        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center justify-center gap-3 sm:justify-start">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12 3C7.03 3 3 4.34 3 6v12c0 1.66 4.03 3 9 3s9-1.34 9-3V6c0-1.66-4.03-3-9-3Zm0 2c4.42 0 7 .96 7 1s-2.58 1-7 1-7-.96-7-1 2.58-1 7-1Zm0 14c-4.42 0-7-.96-7-1v-2.08C6.63 16.57 9.14 17 12 17s5.37-.43 7-1.08V18c0 .04-2.58 1-7 1Zm0-4c-4.42 0-7-.96-7-1v-2.08C6.63 12.57 9.14 13 12 13s5.37-.43 7-1.08V14c0 .04-2.58 1-7 1Zm0-4c-4.42 0-7-.96-7-1V7.92C6.63 8.57 9.14 9 12 9s5.37-.43 7-1.08V10c0 .04-2.58 1-7 1Z"/>
                    </svg>
                </div>

                <h2 class="text-2xl font-extrabold tracking-tight text-gray-900">
                    Dataset <span class="text-blue-600">Hari Ini</span>
                </h2>
            </div>

            <a href="#"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-bold text-gray-800 shadow-sm transition hover:border-blue-200 hover:text-blue-600 hover:shadow-md">
                Lihat Semua
                <span>→</span>
            </a>
        </div>

        {{-- Grid --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Panel kiri --}}
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-8"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 7h7v7"/>
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-base font-extrabold text-gray-900">
                                Dataset Populer
                            </h3>
                            <p class="text-sm text-gray-500">
                                Dataset yang paling sering diakses
                            </p>
                        </div>
                    </div>

                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-600">
                    Populer
                </span>
                </div>

                <div class="space-y-4">
                    {{-- Card item --}}
                    <a href="#"
                       class="group block rounded-2xl border border-gray-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h4 class="text-base font-extrabold text-gray-900 group-hover:text-emerald-600">
                                    Inventaris Sarana Kampus
                                </h4>

                                <div class="mt-3 flex flex-wrap gap-2">
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">
                                    CSV
                                </span>
                                </div>

                                <p class="mt-3 text-sm leading-6 text-slate-500">
                                    Data perangkat, kendaraan, dan perlengkapan operasional kampus.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 flex items-center justify-between gap-3">
                        <span class="text-sm font-bold text-blue-600">
                            Aset Untirta
                        </span>

                            <span
                                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-extrabold text-emerald-600">
                            Terbuka
                        </span>
                        </div>
                    </a>

                    <a href="#"
                       class="group block rounded-2xl border border-gray-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
                        <h4 class="text-base font-extrabold text-gray-900 group-hover:text-emerald-600">
                            Komposisi Pegawai Per Unit Kerja
                        </h4>

                        <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">
                            CSV
                        </span>
                            <span class="rounded-full bg-lime-100 px-3 py-1 text-xs font-bold text-lime-700">
                            WMS
                        </span>
                        </div>

                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Distribusi pegawai aktif berdasarkan unit, jabatan, dan kebutuhan layanan.
                        </p>

                        <div class="mt-5 flex items-center justify-between gap-3">
                        <span class="text-sm font-bold text-blue-600">
                            Kepegawaian Untirta
                        </span>

                            <span
                                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-extrabold text-emerald-600">
                            Terbuka
                        </span>
                        </div>
                    </a>
                </div>
            </div>

            {{-- Panel kanan --}}
            <div class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 11h10M7 15h6"/>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/>
                            </svg>
                        </div>

                        <div>
                            <h3 class="text-base font-extrabold text-gray-900">
                                Dataset Terbaru
                            </h3>
                            <p class="text-sm text-gray-500">
                                Dataset yang baru diperbarui
                            </p>
                        </div>
                    </div>

                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-600">
                    Terbaru
                </span>
                </div>

                <div class="space-y-4">
                    <a href="#"
                       class="group block rounded-2xl border border-gray-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
                        <h4 class="text-base font-extrabold text-gray-900 group-hover:text-blue-600">
                            Jumlah Sarana Pembelajaran Per Kategori
                        </h4>

                        <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">
                            CSV
                        </span>
                        </div>

                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Ringkasan sarana pembelajaran seperti komputer, kursi, meja, dan ruang pendukung.
                        </p>

                        <div class="mt-5 flex items-center justify-between gap-3">
                        <span class="text-sm font-bold text-blue-600">
                            Aset Untirta
                        </span>

                            <span
                                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-extrabold text-emerald-600">
                            Terbuka
                        </span>
                        </div>
                    </a>

                    <a href="#"
                       class="group block rounded-2xl border border-gray-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
                        <h4 class="text-base font-extrabold text-gray-900 group-hover:text-blue-600">
                            Rasio Pegawai Berdasarkan Unit Layanan
                        </h4>

                        <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                            XLSX
                        </span>
                        </div>

                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Data rasio pegawai untuk mendukung evaluasi kapasitas layanan kampus.
                        </p>

                        <div class="mt-5 flex items-center justify-between gap-3">
                        <span class="text-sm font-bold text-blue-600">
                            Kepegawaian Untirta
                        </span>

                            <span
                                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-extrabold text-emerald-600">
                            Terbuka
                        </span>
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </section>
</x-layout>
