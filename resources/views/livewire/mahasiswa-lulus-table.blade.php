<div>
    <form wire:submit="terapkanFilter" class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <input type="text" wire:model="search" placeholder="Cari nama / NIM"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                @error('search')
                    <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <input type="text" wire:model="kode_prodi" placeholder="Kode prodi"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                @error('kode_prodi')
                    <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <input type="text" wire:model="angkatan" placeholder="Angkatan"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                @error('angkatan')
                    <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <input type="text" wire:model="tahun_lulus" placeholder="Tahun lulus"
                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                @error('tahun_lulus')
                    <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row">
            <button type="submit"
                class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-700 disabled:cursor-wait disabled:opacity-70"
                wire:loading.attr="disabled">
                Terapkan Filter
            </button>

            <button type="button" wire:click="resetFilter"
                class="rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-bold text-gray-700 hover:text-blue-600">
                Reset
            </button>
        </div>
    </form>

    <div wire:loading.delay
        class="mb-6 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm font-semibold text-blue-700">
        Memuat data mahasiswa lulus...
    </div>

    @if (!$result['tersedia'])
        <div role="alert" class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
            Data belum dapat dimuat dari SIAKANG. Periksa konfigurasi atau coba kembali beberapa saat lagi.
        </div>
    @endif

    <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Total Data</p>
        <h2 class="mt-2 text-3xl font-extrabold text-gray-900">
            {{ number_format(data_get($result, 'total', 0), 0, ',', '.') }}
        </h2>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" wire:loading.class="opacity-60">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left font-bold text-gray-600">NIM</th>
                        <th class="px-5 py-3 text-left font-bold text-gray-600">Nama</th>
                        <th class="px-5 py-3 text-left font-bold text-gray-600">Prodi</th>
                        <th class="px-5 py-3 text-left font-bold text-gray-600">Angkatan</th>
                        <th class="px-5 py-3 text-left font-bold text-gray-600">Tanggal Lulus</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($mahasiswa as $item)
                        <tr class="hover:bg-gray-50" wire:key="lulusan-{{ data_get($item, 'nim', $loop->index) }}">
                            <td class="px-5 py-4 font-semibold text-gray-900">
                                {{ data_get($item, 'nim') }}
                            </td>
                            <td class="px-5 py-4 text-gray-700">
                                {{ data_get($item, 'nama') }}
                            </td>
                            <td class="px-5 py-4 text-gray-700">
                                {{ data_get($item, 'prodi.nama_prodi_lengkap') }}
                            </td>
                            <td class="px-5 py-4 text-gray-700">
                                {{ data_get($item, 'angkatan') }}
                            </td>
                            <td class="px-5 py-4 text-gray-700">
                                {{ data_get($item, 'tanggal_lulus') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-gray-500">
                                Data mahasiswa lulus belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($mahasiswa->hasPages())
        <div class="mt-6">
            {{ $mahasiswa->onEachSide(1)->links() }}
        </div>
    @endif
</div>
