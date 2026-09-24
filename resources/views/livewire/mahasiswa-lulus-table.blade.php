<div>
    @php
        $kelasKontrol = 'w-full rounded-xl border border-gray-200 px-4 py-3 text-sm outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50';
        $kelasLabel = 'mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500';
    @endphp

    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <label for="filter-cari" class="{{ $kelasLabel }}">Cari nama / NIM</label>
                <input id="filter-cari" type="text" wire:model.live.debounce.500ms="search" placeholder="Nama atau NIM" class="{{ $kelasKontrol }}">
                @error('search')
                    <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="filter-prodi" class="{{ $kelasLabel }}">Program studi</label>
                @if (count($pilihanProdi))
                    <select id="filter-prodi" wire:model.live="kode_prodi" class="{{ $kelasKontrol }}">
                        <option value="">Semua program studi</option>
                        @foreach (collect($pilihanProdi)->groupBy('kelompok') as $kelompok => $daftarProdi)
                            <optgroup label="Jenjang {{ $kelompok }}">
                                @foreach ($daftarProdi as $prodi)
                                    <option value="{{ $prodi['kode'] }}">{{ $prodi['label'] }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                @else
                    <input id="filter-prodi" type="text" wire:model.live.debounce.500ms="kode_prodi" placeholder="Kode prodi, mis. 3332" class="{{ $kelasKontrol }}">
                @endif
                @error('kode_prodi')
                    <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="filter-angkatan" class="{{ $kelasLabel }}">Angkatan</label>
                @if (count($pilihanAngkatan))
                    <select id="filter-angkatan" wire:model.live="angkatan" class="{{ $kelasKontrol }}">
                        <option value="">Semua angkatan</option>
                        @foreach ($pilihanAngkatan as $angkatan)
                            <option value="{{ $angkatan }}">{{ $angkatan }}</option>
                        @endforeach
                    </select>
                @else
                    <input id="filter-angkatan" type="text" wire:model.live.debounce.500ms="angkatan" placeholder="Tahun angkatan, mis. 2021" class="{{ $kelasKontrol }}">
                @endif
                @error('angkatan')
                    <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="filter-tahun-lulus" class="{{ $kelasLabel }}">Tahun lulus</label>
                @if (count($pilihanTahunLulus))
                    <select id="filter-tahun-lulus" wire:model.live="tahun_lulus" class="{{ $kelasKontrol }}">
                        <option value="">Semua tahun</option>
                        @foreach ($pilihanTahunLulus as $tahun)
                            <option value="{{ $tahun }}">{{ $tahun }}</option>
                        @endforeach
                    </select>
                @else
                    <input id="filter-tahun-lulus" type="text" wire:model.live.debounce.500ms="tahun_lulus" placeholder="Tahun lulus, mis. 2025" class="{{ $kelasKontrol }}">
                @endif
                @error('tahun_lulus')
                    <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
            <button type="button" wire:click="resetFilter"
                class="rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-bold text-gray-700 hover:text-blue-600 disabled:cursor-wait disabled:opacity-70"
                wire:loading.attr="disabled">
                Reset
            </button>
        </div>
    </div>

    <div wire:loading.delay
        class="mb-6 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm font-semibold text-blue-700">
        Memuat data mahasiswa lulus...
    </div>

    @if (!$result->success)
        <div role="alert" class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
            <p class="font-bold">Daftar mahasiswa lulus belum bisa dibaca dari SIAKANG.</p>
            <p class="mt-1">
                SIAKANG tidak menyelesaikan permintaan ini. Coba lagi beberapa saat lagi, atau tekan
                <span class="font-semibold">Tarik dari SIAKANG</span> di panel atas halaman ini.
            </p>
            @if ($result->message)
                <details class="mt-2 text-xs text-amber-700">
                    <summary class="cursor-pointer font-semibold">Pesan teknis dari sistem sumber</summary>
                    <p class="mt-1">{{ $result->message }}</p>
                </details>
            @endif
        </div>
    @endif

    <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Total mahasiswa lulus</p>
        <h2 class="mt-2 text-3xl font-extrabold text-gray-900">
            {{ number_format($mahasiswa->total(), 0, ',', '.') }}
        </h2>
        <p class="mt-1 text-xs text-slate-500">
            {{ $adaFilter
                ? 'Sesuai filter yang dipilih.'
                : 'Seluruh lulusan yang tercatat di SIAKANG, semua tahun.' }}
        </p>
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
                                {{ data_get($item, 'prodi.nama_prodi') }}
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
                            <td colspan="5" class="px-5 py-10 text-center text-gray-500">
                                @if ($adaFilter)
                                    <p class="font-semibold text-gray-700">Tidak ada mahasiswa lulus yang cocok dengan filter ini.</p>
                                    <p class="mt-1">Longgarkan atau reset filternya untuk melihat data lainnya.</p>
                                    <button type="button" wire:click="resetFilter"
                                        class="mt-4 rounded-xl border border-gray-200 px-5 py-3 text-sm font-bold text-gray-700 hover:text-blue-600">
                                        Reset filter
                                    </button>
                                @else
                                    <p class="font-semibold text-gray-700">Belum ada data mahasiswa lulus.</p>
                                    <p class="mt-1">Tekan "Tarik dari SIAKANG" di panel atas halaman ini, lalu muat ulang.</p>
                                @endif
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
