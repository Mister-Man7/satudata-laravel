<x-layout>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>

    <section class="max-w-9xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-12 font-sans">
        <div class="mb-6">
            <a href="javascript:history.back()"
               class="inline-flex items-center text-[#4B00FF] hover:text-violet-800 font-medium text-sm transition-colors">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Kembali ke Daftar Ruangan
            </a>
        </div>
        <div
            class="bg-white rounded-[1.25rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-xl font-semibold text-gray-900">
                    {{ $title }}
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Rincian Barang Milik Negara (BMN) yang berada di ruangan ini.
                </p>
            </div>
            <div class="p-6 border-b border-gray-100">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input id="tableSearch" type="text" placeholder="Cari berdasarkan nama barang, merk, atau tahun..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#4B00FF] focus:ring-1 focus:ring-[#4B00FF]">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left">
                    <thead>
                    <tr class="bg-white border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                        <th class="w-12 p-4 text-center">No</th>
                        <th class="p-4">Nama Barang</th>
                        <th class="p-4">Merk / Tipe</th>
                        <th class="p-4 text-center">NUP</th>
                        <th class="p-4 text-center">Kondisi</th>
                    </tr>
                    </thead>
                    <tbody class="text-sm text-gray-700">
                    @forelse ($bmnList as $index => $bmn)
                        @php
                            $badge = match ($bmn['kondisi'] ?? null) {
                                1 => 'bg-green-50 text-green-700 border-green-200',
                                2 => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                3 => 'bg-red-50 text-red-700 border-red-200',
                                default => '',
                            };
                        @endphp
                        <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                            <td class="p-4 text-center text-gray-500">
                                {{ $index + 1 }}
                            </td>
                            <td class="p-4">
                                <div class="font-medium text-gray-900">
                                    {{ $bmn['kode_barang']['nama_kode_barang'] ?? 'Tanpa Nama' }}
                                </div>
                                <div class="mt-0.5 text-xs text-gray-400">
                                    Perolehan:
                                    {{ $bmn['tgl_perolehan_formatted'] ?? '-' }}
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="font-medium">
                                    {{ $bmn['merk'] ?: '-' }}
                                </div>
                                <div class="text-xs text-gray-400">
                                    {{ $bmn['tipe'] ?: 'Tanpa Tipe' }}
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                    <span
                                        class="rounded-md border border-gray-200 bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                                        {{ $bmn['nup'] ?? '-' }}
                                    </span>
                            </td>
                            <td class="p-4 text-center">
                                @if ($badge)
                                    <span class="rounded-full border px-3 py-1 text-xs font-medium {{ $badge }}">
                                            {{ $bmn['kondisi_text'] ?? '-' }}
                                        </span>
                                @else
                                    <span class="text-gray-500">
                                            {{ $bmn['kondisi_text'] ?? '-' }}
                                        </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center text-gray-500">
                                <i class="fa-solid fa-box-open mb-4 block text-4xl text-gray-300"></i>
                                Tidak ada data aset terdaftar di ruangan ini.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-layout>

<script>
    const search = document.getElementById('tableSearch');

    search.addEventListener('keyup', function () {
        const keyword = this.value.toLowerCase();

        document.querySelectorAll('tbody tr').forEach(row => {

            if (row.querySelector('td[colspan]')) return;

            row.style.display = row.textContent
                .toLowerCase()
                .includes(keyword)
                ? ''
                : 'none';
        });
    });
</script>
