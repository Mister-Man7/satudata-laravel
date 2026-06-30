<x-layout>
    <x-slot:title>
        {{ $title ?? 'Akademik' }}
    </x-slot:title>

    @php
        $daftarStatistik = $datas ?? [];
    @endphp
    <x-dashboard.statistik-mahasiswa />

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">
                Ringkasan Data Mahasiswa
            </h1>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($daftarStatistik as $data)
                <x-ui.akademik-card :title="$data['title']" :value="$data['value']" :href="$data['href']" :icon-bg="$data['iconBg']"
                    :card-bg="$data['cardBg'] ?? 'bg-white'" :icon-color="$data['iconColor']" :icon-class="$data['iconClass']"
                    :description="$data['description'] ?? null" :status="$data['status'] ?? null" />
            @empty
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm font-semibold text-amber-700">
                    Data akademik belum siap ditampilkan.
                </div>
            @endforelse
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-7 text-gray-900 text-lg">Persebaran Mahasiswa berdasarkan Fakultas</div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-8 mt-10">
            @foreach ($fakultas as $item)
                <div class="flex items-center gap-4">
                    <div class="{{ $item['color'] }}">
                        <i class="{{ $item['icon'] }} text-3xl"></i>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">
                            {{ $item['name'] }}
                        </p>

                        <h2 class="text-2xl font-semibold text-gray-900">
                            {{ number_format($item['total'], 0, ',', '.') }}
                        </h2>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-layout>
