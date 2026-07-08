<div class="mx-auto max-w-7xl px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Daftar Seluruh Pegawai Aktif</h1>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-gray-700 uppercase font-semibold">
            <tr>
                <th class="px-6 py-4">NIP</th>
                <th class="px-6 py-4">Nama</th>
                <th class="px-6 py-4">Status Kerja</th>
                <th class="px-6 py-4">Jabatan</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
            @forelse ($pegawais as $p)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">{{ $p['nip'] }}</td>
                    <td class="px-6 py-4">{{ $p['nama'] }}</td>
                    <td class="px-6 py-4">{{ $p['status_kerja_nama'] }}</td>
                    <td class="px-6 py-4">{{ $p['jabatan_nama'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">Data tidak ditemukan</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
