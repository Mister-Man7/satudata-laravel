<x-layout>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('akademik') }}" class="text-sm font-bold text-blue-600 hover:text-blue-700">
                ← Kembali ke Dashboard Akademik
            </a>

            <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-gray-900">
                Detail Mahasiswa Lulus
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Filter data mahasiswa lulus berdasarkan tahun lulus, angkatan, program studi, atau pencarian nama/NIM.
            </p>
        </div>

        <form method="GET" action="{{ route('akademik.mahasiswa-lulus') }}"
              class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari nama / NIM"
                    class="rounded-xl border border-gray-200 px-4 py-3 text-sm outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                >

                <input
                    type="text"
                    name="kode_prodi"
                    value="{{ request('kode_prodi') }}"
                    placeholder="Kode prodi"
                    class="rounded-xl border border-gray-200 px-4 py-3 text-sm outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                >

                <input
                    type="text"
                    name="angkatan"
                    value="{{ request('angkatan') }}"
                    placeholder="Angkatan"
                    class="rounded-xl border border-gray-200 px-4 py-3 text-sm outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                >

                <input
                    type="text"
                    name="tahun_lulus"
                    value="{{ request('tahun_lulus') }}"
                    placeholder="Tahun lulus"
                    class="rounded-xl border border-gray-200 px-4 py-3 text-sm outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                >
            </div>

            <div class="mt-4 flex gap-3">
                <button type="submit"
                        class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-700">
                    Terapkan Filter
                </button>

                <a href="{{ route('akademik.mahasiswa-lulus') }}"
                   class="rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-bold text-gray-700 hover:text-blue-600">
                    Reset
                </a>
            </div>
        </form>

        @if (! $result['tersedia'])
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

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
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
                        <tr class="hover:bg-gray-50">
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

        @if ($result['halaman_terakhir'] > 1)
            <nav class="mt-6 flex items-center justify-between" aria-label="Navigasi halaman">
                @if ($result['halaman_sekarang'] > 1)
                    <a href="{{ request()->fullUrlWithQuery(['page' => $result['halaman_sekarang'] - 1]) }}"
                       class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:text-blue-600">
                        ← Sebelumnya
                    </a>
                @else
                    <span></span>
                @endif

                <span class="text-sm font-semibold text-slate-500">
                    Halaman {{ $result['halaman_sekarang'] }} dari {{ $result['halaman_terakhir'] }}
                </span>

                @if ($result['halaman_sekarang'] < $result['halaman_terakhir'])
                    <a href="{{ request()->fullUrlWithQuery(['page' => $result['halaman_sekarang'] + 1]) }}"
                       class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:text-blue-600">
                        Berikutnya →
                    </a>
                @else
                    <span></span>
                @endif
            </nav>
        @endif
    </section>
</x-layout>
