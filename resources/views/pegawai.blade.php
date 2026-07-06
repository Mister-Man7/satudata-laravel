<x-layout>
    <x-slot:title>
        {{ $title ?? 'Akademik' }}
    </x-slot:title>

    @php
        $daftarStatistik = $datas ?? [];
    @endphp

    <x-dashboard.statistik-pegawai title="Total Pegawai" :stats="$statusPegawai ?? []" />

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">
                Persebaran Pegawai berdasarkan Status Kerja
            </h1>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($daftarStatistik as $data)
                <x-ui.pegawai-card :title="$data['label'] ?? '-'" :value="$data['value'] ?? 0" :icon-bg="$data['iconBg'] ?? 'bg-blue-50'" :card-bg="$data['bg'] ?? 'bg-[#4F46E5]'"
                    :icon-color="$data['iconColor'] ?? 'text-indigo-700'" :icon-class="$data['icon'] ?? 'fa-solid fa-users'" />
            @empty
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm font-semibold text-amber-700">
                    Data pegawai belum siap ditampilkan.
                </div>
            @endforelse
        </div>
    </section>
</x-layout>
