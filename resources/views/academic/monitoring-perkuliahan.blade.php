<x-layout>
    <x-slot:title>
        {{ $title ?? 'Monitoring Perkuliahan' }}
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
                    <span class="text-gray-700">Monitoring Perkuliahan</span>
                </nav>
            </div>
            <button onclick="showGantiSemester()" class="rounded-lg bg-blue-500 px-4 py-2 text-sm font-medium text-white hover:bg-blue-600 flex items-center gap-2 self-start">
                <i class="fa-solid fa-calendar-days"></i>
                Ganti Semester
            </button>
        </div>

        <!-- Main Content Card -->
        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
            <!-- Card Header with Info -->
            <div class="border-b border-gray-100 bg-gray-50 px-6 py-5 rounded-t-2xl">
                <h2 class="mb-1 text-lg font-bold text-gray-800">
                    Monitoring Perkuliahan Tahun {{ $semesterInfo['nama_semester'] ?? '-' }}
                </h2>
                <div class="flex items-center gap-2 text-sm text-gray-600">
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

                <div class="ml-auto">
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Cari..."
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
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Aksi</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold">Unit</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold text-right">Jumlah Jadwal</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">RPS Upload</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">RPS Valid</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold text-right">BA Upload</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">BA Valid</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold text-right">Rerata Pertemuan</th>
                            <th class="whitespace-nowrap px-4 py-3 font-semibold text-center">Nilai Masuk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($monitoringData as $row)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="whitespace-nowrap px-4 py-3 text-center text-gray-500">{{ $row['no'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <a href="{{ route('akademik.perkuliahan.detail', ['unitKode' => $row['unit_kode'], 'semester' => $semester]) }}"
                                        class="inline-flex items-center gap-1.5 rounded-md bg-blue-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-600">
                                        <i class="fa-solid fa-list"></i>
                                        Lihat
                                    </a>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="font-semibold text-gray-800">{{ $row['unit_kode'] }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700">{{ number_format($row['jumlah_jadwal']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    @php
                                        $p = $row['rps_upload'];
                                        $color = match(true) {
                                            $p >= 70 => 'bg-green-500 text-white',
                                            $p >= 30 => 'bg-yellow-400 text-white',
                                            $p > 0 => 'bg-red-500 text-white',
                                            default => 'bg-gray-200 text-gray-600',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $color }}">{{ $p }}%</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    @php
                                        $p = $row['rps_valid'];
                                        $color = match(true) {
                                            $p >= 70 => 'bg-green-500 text-white',
                                            $p >= 30 => 'bg-yellow-400 text-white',
                                            $p > 0 => 'bg-red-500 text-white',
                                            default => 'bg-gray-200 text-gray-600',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $color }}">{{ $p }}%</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700">{{ number_format($row['ba_upload']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    @php
                                        $p = $row['ba_valid'];
                                        $color = match(true) {
                                            $p >= 70 => 'bg-green-500 text-white',
                                            $p >= 30 => 'bg-yellow-400 text-white',
                                            $p > 0 => 'bg-red-500 text-white',
                                            default => 'bg-gray-200 text-gray-600',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $color }}">{{ $p }}%</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-700">{{ $row['rerata_pertemuan'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    @php
                                        $p = $row['nilai_masuk'];
                                        $color = match(true) {
                                            $p >= 70 => 'bg-green-500 text-white',
                                            $p >= 30 => 'bg-yellow-400 text-white',
                                            $p > 0 => 'bg-red-500 text-white',
                                            default => 'bg-gray-200 text-gray-600',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $color }}">{{ $p }}%</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <i class="fa-solid fa-inbox text-4xl text-gray-300"></i>
                                        <span class="text-sm">Tidak ada data monitoring perkuliahan untuk ditampilkan</span>
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
                    <span><strong class="font-semibold text-gray-800">{{ count($monitoringData) }}</strong> Unit</span>
                    <span><strong class="font-semibold text-gray-800">{{ $totalJadwal }}</strong> Total Jadwal</span>
                </div>
                <div class="flex items-center gap-1 text-sm text-gray-600">
                    <span>Menampilkan</span>
                    <span class="font-semibold text-gray-800">{{ count($monitoringData) }}</span>
                    <span>baris</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Ganti Semester -->
    <div id="modalGantiSemester" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800">Ganti Semester</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <form method="GET" action="{{ route('akademik.perkuliahan') }}">
                <div class="mb-4">
                    <label for="semester" class="mb-2 block text-sm font-medium text-gray-700">Pilih Semester</label>
                    <select id="semester" name="semester"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="20251" {{ $semester == '20251' ? 'selected' : '' }}>2025/2026 Gasal</option>
                        <option value="20252" {{ $semester == '20252' ? 'selected' : '' }}>2025/2026 Genap</option>
                        <option value="20241" {{ $semester == '20241' ? 'selected' : '' }}>2024/2025 Gasal</option>
                        <option value="20242" {{ $semester == '20242' ? 'selected' : '' }}>2024/2025 Genap</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal()"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-blue-500 px-4 py-2 text-sm font-medium text-white hover:bg-blue-600">
                        Terapkan
                    </button>
                </div>
            </form>
        </div>
    </div>

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
            const rows = table.querySelectorAll('tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('th, td');
                const rowData = Array.from(cells).map(cell => cell.textContent.trim()).join('\t');
                text += rowData + '\n';
            });
            navigator.clipboard.writeText(text).then(() => {
                alert('Tabel berhasil disalin ke clipboard!');
            }).catch(() => {
                // Fallback
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('Tabel berhasil disalin ke clipboard!');
            });
        }

        // Export CSV/Excel
        function exportExcel() {
            const table = document.getElementById('monitoringTable');
            let csv = '\uFEFF'; // BOM for UTF-8
            const rows = table.querySelectorAll('tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('th, td');
                const rowData = Array.from(cells).map(cell => {
                    let text = cell.textContent.trim().replace(/"/g, '""');
                    return '"' + text + '"';
                }).join(',');
                csv += rowData + '\n';
            });
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'monitoring-perkuliahan-{{ $semester }}.csv';
            link.click();
        }

        // Print
        function printTable() {
            const table = document.getElementById('monitoringTable');
            const title = 'Monitoring Perkuliahan Tahun {{ $semesterInfo["nama_semester"] ?? "-" }}';
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>${title}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        h1 { font-size: 18px; margin-bottom: 5px; }
                        p { font-size: 12px; color: #666; margin-bottom: 20px; }
                        table { width: 100%; border-collapse: collapse; font-size: 12px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f3f4f6; font-weight: bold; }
                        td:nth-child(4), td:nth-child(7), td:nth-child(9) { text-align: right; }
                        td:nth-child(1), td:nth-child(5), td:nth-child(6), td:nth-child(8), td:nth-child(10) { text-align: center; }
                        @media print { body { padding: 0; } }
                    </style>
                </head>
                <body>
                    <h1>${title}</h1>
                    <p>Last update at: {{ now()->format('Y-m-d H:i:s') }} WIB</p>
                    ${table.outerHTML}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // PDF (using print dialog with PDF option)
        function exportPDF() {
            exportExcel(); // Sementara export CSV, bisa diganti dengan library PDF nanti
            alert('Untuk PDF, gunakan Print → Save as PDF');
        }


        // Modal Ganti Semester
        function showGantiSemester() {
            document.getElementById('modalGantiSemester').classList.remove('hidden');
            document.getElementById('modalGantiSemester').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('modalGantiSemester').classList.add('hidden');
            document.getElementById('modalGantiSemester').classList.remove('flex');
        }

        // Close modal on backdrop click
        document.getElementById('modalGantiSemester')?.addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
    @endpush
</x-layout>