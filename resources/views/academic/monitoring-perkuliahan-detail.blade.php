<x-layout>
    <x-slot:title>
        {{ $title ?? 'Monitoring Perkuliahan - Detail' }}
    </x-slot:title>

    <section class="mx-auto max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header & Breadcrumb -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 uppercase">Monitoring Perkuliahan</h1>
                <nav class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                    <a href="/" class="hover:text-gray-700">Dashboard</a>
                    <span>/</span>
                    <a href="/akademik" class="hover:text-gray-700">Akademik</a>
                    <span>/</span>
                    <a href="{{ route('akademik.perkuliahan', ['semester' => $semester]) }}" class="hover:text-gray-700">Monitoring Perkuliahan</a>
                    <span>/</span>
                    @if(($viewType ?? '') === 'mk_list' && !empty($selectedKodeProdi))
                        <a href="{{ route('akademik.perkuliahan.detail', ['unitKode' => $unit['kode'], 'semester' => $semester]) }}" class="hover:text-gray-700">{{ $unit['kode'] }}</a>
                        <span>/</span>
                        <span class="text-gray-700 font-semibold">{{ $selectedProdiName }}</span>
                    @else
                        <span class="text-gray-700 font-semibold">{{ $unit['kode'] }}</span>
                    @endif
                </nav>
            </div>

            @if(($viewType ?? '') === 'mk_list' && !empty($selectedKodeProdi))
                <a href="{{ route('akademik.perkuliahan.detail', ['unitKode' => $unit['kode'], 'semester' => $semester]) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 self-start transition-colors shadow-sm">
                    <i class="fa-solid fa-arrow-left"></i>
                    Kembali ke Daftar Prodi {{ $unit['kode'] }}
                </a>
            @else
                <a href="{{ route('akademik.perkuliahan', ['semester' => $semester]) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 self-start transition-colors shadow-sm">
                    <i class="fa-solid fa-arrow-left"></i>
                    Kembali ke Fakultas
                </a>
            @endif
        </div>

        <!-- Main Content Card -->
        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
            <!-- Card Header -->
            <div class="border-b border-gray-100 bg-gray-50 px-6 py-5 rounded-t-2xl">
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2.5 py-0.5 rounded-md bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100">{{ $unit['kode'] }}</span>
                    <h2 class="text-xl font-bold text-gray-800">
                        @if(($viewType ?? '') === 'mk_list' && !empty($selectedProdiName))
                            {{ $selectedProdiName }} (Kode: {{ $selectedKodeProdi }})
                        @else
                            {{ $unit['nama'] }}
                        @endif
                    </h2>
                </div>
                <div class="text-sm text-gray-600">
                    @if(($viewType ?? '') === 'prodi_list')
                        Monitoring Perkuliahan Tingkat Program Studi - Tahun {{ $semesterInfo['nama_semester'] ?? '-' }}
                    @else
                        Daftar Mata Kuliah & Jadwal Kelas - Tahun {{ $semesterInfo['nama_semester'] ?? '-' }}
                    @endif
                </div>
                <div class="mt-1 flex items-center gap-2 text-sm text-gray-500">
                    <i class="fa-regular fa-clock text-gray-400"></i>
                    <span>Last update at: {{ now()->format('Y-m-d H:i:s') }} WIB</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-6 py-4">
                <button onclick="copyTable()"
                    class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <i class="fa-regular fa-copy"></i> Copy
                </button>
                <button onclick="exportPDF()"
                    class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <i class="fa-regular fa-file-pdf"></i> PDF
                </button>
                <button onclick="exportExcel()"
                    class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <i class="fa-regular fa-file-excel"></i> Excel
                </button>
                <button onclick="printTable()"
                    class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-print"></i> Print
                </button>

                <div class="ml-auto flex flex-wrap items-center gap-3">
                    @if(($viewType ?? '') === 'mk_list' && !empty($allDosenList))
                        <!-- Filter Dosen -->
                        <form method="GET" action="{{ route('akademik.perkuliahan.detail', ['unitKode' => $unit['kode']]) }}" class="flex items-center gap-2">
                            <input type="hidden" name="semester" value="{{ $semester }}">
                            @if(!empty($selectedKodeProdi))
                                <input type="hidden" name="kode_prodi" value="{{ $selectedKodeProdi }}">
                            @endif
                            <select name="nip" onchange="this.form.submit()"
                                class="rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">Semua Dosen Pengampu</option>
                                @foreach($allDosenList as $dosen)
                                    <option value="{{ $dosen['nip'] }}" {{ ($filterNip ?? '') === $dosen['nip'] ? 'selected' : '' }}>
                                        {{ $dosen['nama'] ?? $dosen['nip'] }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif

                    <!-- Search -->
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="{{ ($viewType ?? '') === 'prodi_list' ? 'Cari program studi...' : 'Cari mata kuliah...' }}"
                            class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 sm:w-64">
                        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
            </div>

            <!-- LEVEL 2: TABEL PROGRAM STUDI (PRODI) -->
            @if(($viewType ?? '') === 'prodi_list')
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto text-sm" id="monitoringTable">
                        <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">#</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">Aksi</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Kode Prodi</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama Program Studi</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold text-right">Jumlah MK</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold text-right">Jumlah Jadwal</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold text-right">Total SKS</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold text-right">SKS Teori</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold text-right">SKS Praktik</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">Rerata Pertemuan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($prodiRows as $row)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="whitespace-nowrap px-4 py-3 text-center text-gray-500">{{ $row['no'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-center">
                                        <a href="{{ $row['aksi_url'] }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition-colors">
                                            <i class="fa-solid fa-list"></i>
                                            Lihat Jadwal
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-bold text-gray-900">
                                        {{ $row['kode_prodi'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-800 font-medium">
                                        {{ $row['nama_prodi'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-800">
                                        {{ number_format($row['jumlah_mk']) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-gray-600">
                                        {{ number_format($row['jumlah_jadwal']) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-bold text-purple-700">
                                        {{ number_format($row['total_sks']) }} SKS
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-gray-600">
                                        {{ number_format($row['sks_teori']) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-gray-600">
                                        {{ number_format($row['sks_praktik']) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-center">
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-100">
                                            {{ number_format($row['rerata_pertemuan'], 1) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center gap-3">
                                            <i class="fa-solid fa-inbox text-4xl text-gray-300"></i>
                                            <span class="text-sm">Tidak ada program studi terdaftar untuk unit {{ $unit['kode'] }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Footer Agregasi Prodi -->
                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-gray-100 px-6 py-4 rounded-b-2xl bg-gray-50">
                    <div class="flex items-center gap-6 text-sm text-gray-600">
                        <span><strong class="font-semibold text-gray-800">{{ $totalProdi }}</strong> Program Studi</span>
                        <span><strong class="font-semibold text-gray-800">{{ number_format($totalMK) }}</strong> Total Mata Kuliah</span>
                        <span><strong class="font-semibold text-purple-700">{{ number_format($totalSKS) }} SKS</strong> Total</span>
                    </div>
                    <div class="flex items-center gap-1 text-sm text-gray-600">
                        <span>Menampilkan</span>
                        <span class="font-semibold text-gray-800">{{ $totalProdi }}</span>
                        <span>baris</span>
                    </div>
                </div>
            @else
                <!-- LEVEL 3: TABEL MATA KULIAH & JADWAL KELAS -->
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto text-sm" id="monitoringTable">
                        <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">#</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Kode MK</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Nama Mata Kuliah</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">SKS Total</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">SKS Teori</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">SKS Praktik</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">Kurikulum</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Jam Kuliah</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Kelas & Ruang</th>
                                <th class="whitespace-nowrap px-4 py-3 font-semibold">Dosen Pengampu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($jadwalRows as $index => $row)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="whitespace-nowrap px-4 py-3 text-center text-gray-500">{{ $index + 1 }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-bold text-gray-900">
                                        {{ $row['kode_mk'] }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-800">
                                        {{ $row['nama_mk'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-center font-bold text-indigo-700">
                                        {{ $row['sks'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-center text-gray-600">
                                        {{ $row['sks_teori'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-center text-gray-600">
                                        {{ $row['sks_praktik'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-center font-medium text-gray-500">
                                        {{ $row['tahun_terbit'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-700">
                                        <span class="inline-flex items-center gap-1 text-xs text-gray-600">
                                            <i class="fa-regular fa-calendar text-gray-400"></i>
                                            {{ $row['jam_kuliah'] }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-700 text-xs">
                                        <span class="font-semibold text-gray-800">{{ $row['kelas'] }}</span>
                                        @if(!empty($row['ruang']) && $row['ruang'] !== '-')
                                            <span class="text-gray-400">({{ $row['ruang'] }})</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-700">
                                        @if(!empty($row['nip_dosen']) && $row['nip_dosen'] !== '-')
                                            <a href="{{ route('akademik.perkuliahan.dosen', ['nip' => $row['nip_dosen'], 'semester' => $semester]) }}"
                                               class="text-blue-600 hover:text-blue-800 hover:underline font-medium"
                                               title="Lihat Profil Dosen">
                                                {{ $row['nama_dosen'] }}
                                            </a>
                                        @else
                                            {{ $row['nama_dosen'] }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center gap-3">
                                            <i class="fa-solid fa-inbox text-4xl text-gray-300"></i>
                                            <span class="text-sm">Tidak ada data jadwal untuk unit {{ $unit['kode'] }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Summary Footer -->
                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-gray-100 px-6 py-4 rounded-b-2xl bg-gray-50">
                    <div class="flex items-center gap-6 text-sm text-gray-600">
                        <span><strong class="font-semibold text-gray-800">{{ $totalJadwal }}</strong> Total Mata Kuliah / Jadwal</span>
                    </div>
                    <div class="flex items-center gap-1 text-sm text-gray-600">
                        <span>Menampilkan</span>
                        <span class="font-semibold text-gray-800">{{ $totalJadwal }}</span>
                        <span>baris</span>
                    </div>
                </div>
            @endif
        </div>
    </section>

    @push('scripts')
    <script>
        // Search
        document.getElementById('searchInput')?.addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            document.querySelectorAll('#monitoringTable tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(search) ? '' : 'none';
            });
        });

        // Copy Table
        function copyTable() {
            const table = document.getElementById('monitoringTable');
            let text = '';
            table.querySelectorAll('tr').forEach(row => {
                const cells = row.querySelectorAll('th, td');
                text += Array.from(cells).map(c => c.textContent.trim()).join('\t') + '\n';
            });
            navigator.clipboard.writeText(text).then(() => alert('Tabel berhasil disalin ke clipboard!'));
        }

        // Export Excel (CSV)
        function exportExcel() {
            const table = document.getElementById('monitoringTable');
            let csv = '\uFEFF';
            table.querySelectorAll('tr').forEach(row => {
                const cells = row.querySelectorAll('th, td');
                csv += Array.from(cells).map(c => '"' + c.textContent.trim().replace(/"/g, '""') + '"').join(',') + '\n';
            });
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'monitoring-{{ $unit["kode"] }}-semester-{{ $semester }}.csv';
            link.click();
        }

        // Print
        function printTable() {
            const table = document.getElementById('monitoringTable');
            const title = 'Monitoring Perkuliahan - {{ $unit["kode"] }} ({{ $unit["nama"] }})';
            const w = window.open('', '_blank');
            w.document.write(`<html><head><title>${title}</title><style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h1 { font-size: 18px; margin-bottom: 5px; }
                p { font-size: 12px; color: #666; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; font-size: 12px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f3f4f6; font-weight: bold; }
            </style></head><body>
                <h1>${title}</h1>
                <p>Last update: {{ now()->format('Y-m-d H:i:s') }} WIB</p>
                ${table.outerHTML}
            </body></html>`);
            w.document.close();
            w.print();
        }

        function exportPDF() {
            exportExcel();
            alert('Untuk PDF, gunakan Print → Save as PDF');
        }
    </script>
    @endpush
</x-layout>