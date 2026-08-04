<x-layout>
    <x-slot:title>
        {{ $title ?? 'Pegawai' }}
    </x-slot:title>

    {{-- Section 1: Chart Status Pegawai (Doughnut Chart) --}}
    <x-ui.pegawai-chart :chartData="$chartStatusPegawai ?? ['labels' => [], 'data' => [], 'total' => 0]" title="Status Pegawai" type="doughnut" />

    {{-- Section 2: Persebaran Status Kerja & Level Pegawai dalam satu section (kiri - kanan) --}}
    <section class="mb-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-ui.pegawai-chart :chartData="$chartStatusKerja ?? ['labels' => [], 'data' => [], 'total' => 0]" title="Persebaran Status Kerja" type="bar" :standalone="false" />

            <x-ui.pegawai-chart :chartData="$chartLevelPegawai ?? ['labels' => [], 'data' => [], 'total' => 0]" title="Persebaran Level Pegawai" type="polarArea" :standalone="false" />
        </div>
    </section>
</x-layout>