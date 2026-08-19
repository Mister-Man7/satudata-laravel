<x-layout>
    <x-slot:title>
        {{ $title ?? 'Monitoring Perkuliahan - Detail' }}
    </x-slot:title>

    <section class="mx-auto max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 uppercase">Monitoring Perkuliahan</h1>
                <nav class="mt-2 flex items-center gap-2 text-sm text-gray-500">
                    <a href="/" class="hover:text-gray-700">Dashboard</a>
                    <span>/</span>
                    <a href="/akademik/perkuliahan" class="hover:text-gray-700">Perkuliahan</a>
                    <span>/</span>
                    <a href="/akademik/perkuliahan" class="hover:text-gray-700">Monitoring Perkuliahan</a>
                    <span>/</span>
                    <span class="text-gray-700">{{ $unit['kode'] }}</span>
                </nav>
            </div>
            <a href="{{ route('akademik.perkuliahan', ['semester' => $semester]) }}"
                class="inline-flex items-center gap-2 rounded-lg bg-teal-500 px-4 py-2 text-sm font-medium text-white hover:bg-teal-600 self-start">
                <i class="fa-solid fa-arrow-left"></i>
                Kembali
            </a>
        </div>

        <!-- Main Content Card -->
        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
            <!-- Card Header with Info -->
            <div class="border-b border-gray-100 bg-gray-50 px-6 py-5 rounded-t-2xl">
                <h2 class="mb-1 text-2xl font-bold text-gray-800">{{ $unit['kode'] }}</h2>
                <p class="text-sm text-gray-600 mb-1">{{ $unit['nama'] }}</p>
                <div class="text-sm text-gray-600">
                    Monitoring Perkuliahan Tahun {{ $semesterInfo['nama_semester'] ?? '-' }}
                </div>
                <div class="mt-1 flex items-center gap-2 text-sm text-gray-600">
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

                <div class="ml-auto flex items-center gap-3">
                    <!-- Filter Dosen -->
                    <form method="GET" action="{{ route('akademik.perkuliahan.detail', ['unitKode' => $unit['kode']]) }}" class="flex items-center gap-2">
                        <input type="hidden" name="semester" value="{{ $semester }}">
                        <select name="nip" onchange="this.form.submit()"
                            class="rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="">Semua Dosen</option>
                            @foreach($allDosenList as $dosen)
                                <option value="{{ $dosen['nip'] }}" {{ ($filterNip ?? '') === $dosen['nip'] ? 'selected' : '' }}>
                                    {{ $dosen['nama'] ?? $dosen['nip'] }}
                                </option>
                            @endforeach
                        </select>
                        @if($filterNip)
                            <a href="{{ route('akademik.perkuliahan.detail', ['unitKode' => $unit['kode'], 'semester' => $semester]) }}" 
                                class="rounded-lg bg-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        @endif
                    </form>

                    <!-- Search -->
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Search:"
                            class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 sm:w-64">
                        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto text-sm" id="monitoringTable">
                    <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">#</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Jadwal</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">SKS</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">RPS Upload</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">RPS Valid</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">BA Upload</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">BA Valid</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">Jumlah Pertemuan</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">Nilai Masuk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($jadwalRows as $index => $row)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="whitespace-nowrap px-4 py-3 text-center text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-gray-800">{{ $row['nama_mk'] }} ({{ $row['kode_mk'] }})</span>
                                        <div class="flex items-center gap-2 mt-1 text-xs text-gray-500">
                                            <span class="inline-flex items-center gap-1">
                                                <i class="fa-regular fa-calendar"></i>
                                                {{ $row['jam_kuliah'] }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-500">
                                            <span class="inline-flex items-center gap-1">
                                                <i class="fa-regular fa-user"></i>
                                                @if(!empty($row['nip_dosen']) && $row['nip_dosen'] !== '-')
                                                    <a href="{{ route('akademik.perkuliahan.dosen', ['nip' => $row['nip_dosen'], 'semester' => $semester]) }}"
                                                       class="text-blue-600 hover:text-blue-800 hover:underline font-medium"
                                                       title="Lihat Profil Dosen">
                                                        {{ $row['nama_dosen'] }}
                                                    </a>
                                                @else
                                                    {{ $row['nama_dosen'] }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center text-gray-700">{{ $row['sks'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    @php
                                        $rpsU = $row['rps_upload'];
                                        $badgeColor = ($rpsU === '✓') ? 'bg-green-500 text-white' : 'bg-red-500 text-white';
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $badgeColor }}">{{ $rpsU }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    @php
                                        $rpsV = $row['rps_valid'];
                                        $badgeColor = ($rpsV === '✓') ? 'bg-green-500 text-white' : 'bg-red-500 text-white';
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $badgeColor }}">{{ $rpsV }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center text-gray-700">{{ $row['ba_upload'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center text-gray-700">{{ $row['ba_valid'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center text-gray-700">{{ $row['pertemuan'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    @php
                                        $nilai = $row['nilai_masuk'];
                                        $badgeColor = ($nilai === '✓') ? 'bg-green-500 text-white' : 'bg-red-500 text-white';
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $badgeColor }}">{{ $nilai }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
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
                    <span><strong class="font-semibold text-gray-800">{{ $totalJadwal }}</strong> Total Jadwal</span>
                </div>
                <div class="flex items-center gap-1 text-sm text-gray-600">
                    <span>Menampilkan</span>
                    <span class="font-semibold text-gray-800">{{ $totalJadwal }}</span>
                    <span>baris</span>
                </div>
            </div>
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
            navigator.clipboard.writeText(text).then(() => alert('Tabel berhasil disalin!'));
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
                body { font-family: Arial; padding: 20px; }
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
            alert('Untuk PDF, gunakan Print → Save as PDF');
            printTable();
        }
    </script>
    @endpush
</x-layout>