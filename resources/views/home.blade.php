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
</x-layout>
