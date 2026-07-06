<x-layout>
    <x-slot:title>
        {{ $title ?? 'Akademik' }}
    </x-slot:title>

    @php
        $daftarStatistik = $datas ?? [];
    @endphp

    <x-dashboard.statistik-pegawai title="Total Pegawai" :stats="$statusPegawai ?? []"/>

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="mt-4 text-lg font-medium tracking-tight text-gray-600 sm:text-2xl">
                Persebaran Pegawai berdasarkan Status Kerja
            </h1>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($daftarStatistik as $data)
                <x-ui.pegawai-card :title="$data['label'] ?? '-'" :value="$data['value'] ?? 0"
                                   :icon-bg="$data['iconBg'] ?? 'bg-blue-50'" :card-bg="$data['bg'] ?? 'bg-[#4F46E5]'"
                                   :icon-color="$data['iconColor'] ?? 'text-indigo-700'"
                                   :icon-class="$data['icon'] ?? 'fa-solid fa-users'"/>
            @empty
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm font-semibold text-amber-700">
                    Data pegawai belum siap ditampilkan.
                </div>
            @endforelse
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h2 class="text-lg font-medium tracking-tight text-gray-600 sm:text-2xl">
                Persebaran Pegawai berdasarkan Level dan Jabatan
            </h2>
        </div>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-5">
                @forelse ($levelPegawai ?? [] as $level)
                    <x-ui.pegawai-card :title="$level['label']" :value="$level['value']"
                                       :card-bg="$level['bg'] ?? 'bg-white'"
                                       :text-color="$level['textColor'] ?? 'text-gray-900'"
                                       :icon-bg="$level['iconBg'] ?? 'bg-blue-50'"
                                       :icon-color="$level['iconColor'] ?? 'text-blue-600'"
                                       :icon-class="$level['icon'] ?? 'fa-solid fa-user-graduate'"/>
                @empty
                    <div class="col-span-2 rounded-2xl bg-gray-50 p-5 text-center text-sm text-gray-500">
                        Data level pegawai tidak tersedia.
                    </div>
                @endforelse
            </div>
            <div class="lg:col-span-1">
                <div
                    class="group relative flex h-full flex-col justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-amber-400 to-orange-600 p-8 shadow-md transition-all hover:-translate-y-1 hover:shadow-xl">
                    <i
                        class="fa-solid fa-award absolute -right-4 -top-4 text-9xl text-white opacity-20 transition-transform duration-500 group-hover:scale-110 rotate-12"></i>
                    <div class="relative z-10">
                        <p class="text-lg font-semibold text-amber-50">Jabatan Fungsional</p>
                        <h3 class="mt-2 text-4xl font-black text-white">
                            Guru Besar
                        </h3>
                        <div class="mt-6 flex items-end gap-3">
                            <span class="text-6xl font-black tracking-tighter text-white">
                                {{ number_format($guruBesar ?? 0, 0, ',', '.') }}
                            </span>
                            <span class="mb-2 text-lg font-medium text-amber-100">Orang</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layout>
