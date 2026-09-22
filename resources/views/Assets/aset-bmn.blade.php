<x-layout>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>

    @php
        $items = collect($bmnList)->values();
        $kondisiMap = config('aset.kondisi', []);
        $kondisiRef = fn (string $kunci) => $kondisiMap[$kunci] ?? ['kode' => null, 'label' => null];
        $hitungKondisi = fn (string $kunci) => $items->filter(
            fn ($i) => (int) ($i['kondisi'] ?? 0) === (int) $kondisiRef($kunci)['kode']
                || strtolower(trim($i['kondisi_text'] ?? '')) === strtolower((string) $kondisiRef($kunci)['label'])
        )->count();
        $totalItems = $items->count();
        $totalBaik = $hitungKondisi('baik');
        $totalRusakRingan = $hitungKondisi('rusak_ringan');
        $totalRusakBerat = $hitungKondisi('rusak_berat');
        $totalNilaiRuangan = $items->sum(fn($i) => (float)($i['nilai_perolehan'] ?? 0));
        $pctBaik = $totalItems > 0 ? round(($totalBaik / $totalItems) * 100, 1) : 0;
    @endphp

    <div class="w-full font-sans"
         x-data="{
            search: '',
            filterKondisi: 'all',
            sortBy: 'default',
            pageSize: 10,
            currentPage: 1,
            items: @js($items),
            kondisiMap: @js($kondisiMap),

            // Kunci kondisi (baik/rusak_ringan/rusak_berat) dari kode atau teksnya.
            kondisiKunci(item) {
                const k = String(item.kondisi ?? '');
                const kt = (item.kondisi_text ?? '').toLowerCase().trim();
                const cocok = Object.entries(this.kondisiMap).find(([, v]) =>
                    String(v.kode) === k || (kt !== '' && kt === String(v.label).toLowerCase()));
                return cocok ? cocok[0] : null;
            },

            matches(item) {
                // Filter Kondisi
                if (this.filterKondisi !== 'all') {
                    const k = String(item.kondisi ?? '');
                    const kt = (item.kondisi_text ?? '').toLowerCase();
                    const target = Object.values(this.kondisiMap).find(v => String(v.kode) === this.filterKondisi);
                    if (target && k !== String(target.kode) && !kt.includes(String(target.label).toLowerCase())) return false;
                }

                // Filter Search
                if (!this.search.trim()) return true;
                const q = this.search.toLowerCase().trim();
                const nama = (item.nama_kode_barang || (item.kode_barang && item.kode_barang.nama_kode_barang) || '').toLowerCase();
                const jenis = (item.nama_jenis_barang || '').toLowerCase();
                const merk = (item.merk || '').toLowerCase();
                const tipe = (item.tipe || '').toLowerCase();
                const nup = String(item.nup || '').toLowerCase();
                const kode = String(item.id_kode_barang || '').toLowerCase();
                return nama.includes(q) || jenis.includes(q) || merk.includes(q) || tipe.includes(q) || nup.includes(q) || kode.includes(q);
            },

            get filteredItems() {
                let result = this.items.filter(i => this.matches(i));

                if (this.sortBy === 'nama_asc') {
                    result.sort((a, b) => (a.nama_kode_barang || '').localeCompare(b.nama_kode_barang || ''));
                } else if (this.sortBy === 'nama_desc') {
                    result.sort((a, b) => (b.nama_kode_barang || '').localeCompare(a.nama_kode_barang || ''));
                } else if (this.sortBy === 'nilai_desc') {
                    result.sort((a, b) => (Number(b.nilai_perolehan) || 0) - (Number(a.nilai_perolehan) || 0));
                } else if (this.sortBy === 'nilai_asc') {
                    result.sort((a, b) => (Number(a.nilai_perolehan) || 0) - (Number(b.nilai_perolehan) || 0));
                } else if (this.sortBy === 'nup_asc') {
                    result.sort((a, b) => (Number(a.nup) || 0) - (Number(b.nup) || 0));
                }
                return result;
            },

            get totalPages() {
                if (this.pageSize === 'all') return 1;
                return Math.max(1, Math.ceil(this.filteredItems.length / parseInt(this.pageSize)));
            },

            get paginatedItems() {
                if (this.pageSize === 'all') return this.filteredItems;
                const size = parseInt(this.pageSize);
                const start = (this.currentPage - 1) * size;
                return this.filteredItems.slice(start, start + size);
            },

            get startRecord() {
                if (this.filteredItems.length === 0) return 0;
                if (this.pageSize === 'all') return 1;
                return (this.currentPage - 1) * parseInt(this.pageSize) + 1;
            },

            get endRecord() {
                if (this.pageSize === 'all') return this.filteredItems.length;
                return Math.min(this.filteredItems.length, this.currentPage * parseInt(this.pageSize));
            },

            changePage(p) {
                if (p < 1) p = 1;
                if (p > this.totalPages) p = this.totalPages;
                this.currentPage = p;
            },

            get paginationRange() {
                const total = this.totalPages;
                const current = this.currentPage;
                if (total <= 7) {
                    return Array.from({ length: total }, (_, i) => i + 1);
                }
                const delta = 1;
                const range = [];
                for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
                    range.push(i);
                }
                if (current - delta > 2) {
                    range.unshift('...');
                }
                if (current + delta < total - 1) {
                    range.push('...');
                }
                range.unshift(1);
                if (total > 1) {
                    range.push(total);
                }
                return range;
            },

            formatCurrency(val) {
                if (!val || isNaN(val) || Number(val) <= 0) return '-';
                return 'Rp ' + Number(val).toLocaleString('id-ID');
            },

            formatDate(d) {
                if (!d) return '-';
                try {
                    const date = new Date(d);
                    if (isNaN(date.getTime())) return d;
                    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                } catch(e) {
                    return d;
                }
            }
         }">

        {{-- Breadcrumb Navigation --}}
        <nav class="mb-5 flex items-center gap-2 text-xs sm:text-sm font-medium text-gray-500" aria-label="Breadcrumb">
            <a href="{{ route('aset.index') }}" class="hover:text-blue-600 transition-colors flex items-center gap-1.5">
                <i class="fa-solid fa-boxes-stacked text-xs"></i>
                <span>Aset</span>
            </a>
            <i class="fa-solid fa-chevron-right text-[10px] text-gray-400"></i>
            <a href="javascript:history.back()" class="hover:text-blue-600 transition-colors flex items-center gap-1">
                <span>Daftar Ruangan</span>
            </a>
            <i class="fa-solid fa-chevron-right text-[10px] text-gray-400"></i>
            <span class="text-gray-900 font-semibold truncate">{{ $ruanganName ?? 'Ruangan' }}</span>
        </nav>

        {{-- Room Header & Quick Stats Card --}}
        <div class="mb-6 bg-white rounded-2xl  p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                
                {{-- Room info --}}
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0 shadow-2xs">
                        <i class="fa-solid fa-door-open"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md">
                                RUANG OPERASIONAL
                            </span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1 tracking-tight">
                            {{ $ruanganName ?? $title }}
                        </h1>
                        <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                            Rincian Barang Milik Negara (BMN) dan inventaris terdaftar pada ruangan ini.
                        </p>
                    </div>
                </div>

                {{-- Room Metric Pills --}}
                    <div class="flex flex-wrap items-center gap-2.5 pt-3 lg:pt-0 border-t lg:border-t-0 border-gray-100">

                        {{-- Total BMN --}}
                        <div class="px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-center min-w-[100px]">
                            <span class="flex items-center justify-center gap-1 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">
                                <i class="fa-solid fa-boxes-stacked text-[9px]"></i> Total BMN
                            </span>
                            <span class="text-lg font-extrabold font-mono text-slate-900 mt-0.5 block">
                                {{ number_format($totalItems, 0, ',', '.') }}
                            </span>
                        </div>

                        {{-- Kondisi Baik --}}
                        <div class="px-4 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-center min-w-[90px]">
                            <span class="flex items-center justify-center gap-1 text-[10px] font-semibold text-emerald-600 uppercase tracking-wider">
                                <i class="fa-solid fa-circle-check text-[9px]"></i> Kondisi Baik
                            </span>
                            <span class="text-lg font-extrabold font-mono text-emerald-700 mt-0.5 block">
                                {{ number_format($totalBaik, 0, ',', '.') }}
                            </span>
                        </div>

                        {{-- Rusak Ringan --}}
                        @if($totalRusakRingan > 0)
                        <div class="px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-center min-w-[90px]">
                            <span class="flex items-center justify-center gap-1 text-[10px] font-semibold text-amber-600 uppercase tracking-wider">
                                <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> Rusak Ringan
                            </span>
                            <span class="text-lg font-extrabold font-mono text-amber-700 mt-0.5 block">
                                {{ number_format($totalRusakRingan, 0, ',', '.') }}
                            </span>
                        </div>
                        @endif

                        {{-- Rusak Berat --}}
                        @if($totalRusakBerat > 0)
                        <div class="px-4 py-2.5 rounded-xl bg-red-50 border border-red-200 text-center min-w-[90px]">
                            <span class="flex items-center justify-center gap-1 text-[10px] font-semibold text-red-600 uppercase tracking-wider">
                                <i class="fa-solid fa-circle-xmark text-[9px]"></i> Rusak Berat
                            </span>
                            <span class="text-lg font-extrabold font-mono text-red-700 mt-0.5 block">
                                {{ number_format($totalRusakBerat, 0, ',', '.') }}
                            </span>
                        </div>
                        @endif

                        {{-- Nilai Perolehan --}}
                        @if($totalNilaiRuangan > 0)
                        <div class="px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-center min-w-[130px]">
                            <span class="flex items-center justify-center gap-1 text-[10px] font-semibold text-amber-600 uppercase tracking-wider">
                                <i class="fa-solid fa-coins text-[9px]"></i> Nilai Perolehan
                            </span>

                            <span class="text-lg font-extrabold font-mono text-[#d4a017] mt-0.5 block">
                                @if($totalNilaiRuangan >= 1000000000)
                                    Rp {{ number_format($totalNilaiRuangan / 1000000000, 2, ',', '.') }} M
                                @elseif($totalNilaiRuangan >= 1000000)
                                    Rp {{ number_format($totalNilaiRuangan / 1000000, 1, ',', '.') }} Jt
                                @else
                                    Rp {{ number_format($totalNilaiRuangan, 0, ',', '.') }}
                                @endif
                            </span>
                        </div>
                        @endif

                    </div>
        </div>

        {{-- Table Container with Integrated Toolbar --}}
        <div class="bg-white rounded-2xl  shadow-sm overflow-hidden">
            
            {{-- Toolbar: Search + Kondisi Filter + Sort + Page Size --}}
            <div class="p-4 sm:p-5 border-b border-gray-100 bg-slate-50/50 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                
                {{-- Search Bar --}}
                <div class="relative flex-1 max-w-md">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-gray-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text"
                           x-model="search"
                           @input="currentPage = 1"
                           placeholder="Cari nama barang, jenis, merk, tipe, atau NUP..."
                           class="w-full pl-10 pr-8 py-2 bg-white border border-gray-200 rounded-xl text-sm placeholder-gray-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all">
                    <button type="button"
                            x-show="search.length > 0"
                            @click="search = ''; currentPage = 1;"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                            x-cloak>
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>

                {{-- Filters & Controls --}}
                <div class="flex flex-wrap items-center gap-3 self-start lg:self-auto">
                    {{-- Filter Kondisi --}}
                    <select x-model="filterKondisi"
                            @change="currentPage = 1"
                            class="py-2 pl-3 pr-8 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 cursor-pointer">
                        <option value="all">Semua Kondisi</option>
                        @foreach ($kondisiMap as $entriKondisi)
                            <option value="{{ $entriKondisi['kode'] }}">{{ $entriKondisi['label'] }}</option>
                        @endforeach
                    </select>

                    {{-- Sort By --}}
                    <select x-model="sortBy"
                            @change="currentPage = 1"
                            class="py-2 pl-3 pr-8 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 cursor-pointer">
                        <option value="default">Urutan Standar</option>
                        <option value="nama_asc">Nama (A - Z)</option>
                        <option value="nama_desc">Nama (Z - A)</option>
                        <option value="nilai_desc">Nilai Tertinggi</option>
                        <option value="nilai_asc">Nilai Terendah</option>
                        <option value="nup_asc">NUP (Urut)</option>
                    </select>

                    {{-- Counter info --}}
                    <span class="text-xs font-medium text-gray-500 whitespace-nowrap pl-1">
                        Total: <strong class="text-gray-900 font-mono" x-text="filteredItems.length"></strong> unit
                    </span>
                </div>

            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-sm">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-200/80 text-[11px] uppercase tracking-wider font-semibold text-gray-500">
                            <th class="w-14 py-3.5 px-4 text-center">No</th>
                            <th class="py-3.5 px-4">Nama Barang & Identitas</th>
                            <th class="py-3.5 px-4">Merk / Tipe</th>
                            <th class="py-3.5 px-4 text-center">NUP</th>
                            <th class="py-3.5 px-4 text-right">Nilai Perolehan</th>
                            <th class="py-3.5 px-4 text-center">Kondisi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        <template x-for="(item, idx) in paginatedItems" :key="item.id_bmn || idx">
                            <tr class="hover:bg-blue-50/40 transition-colors">
                                
                                {{-- No --}}
                                <td class="py-3.5 px-4 text-center text-xs font-mono text-gray-400"
                                    x-text="(currentPage - 1) * (pageSize === 'all' ? 0 : pageSize) + idx + 1">
                                </td>

                                {{-- Nama Barang & Info --}}
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-gray-900 leading-snug"
                                         x-text="item.nama_kode_barang || (item.kode_barang && item.kode_barang.nama_kode_barang) || 'Barang Tanpa Nama'">
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-gray-500">
                                        <template x-if="item.nama_jenis_barang">
                                            <span class="text-xs font-medium bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded"
                                                  x-text="item.nama_jenis_barang">
                                            </span>
                                        </template>
                                        <template x-if="item.tgl_perolehan">
                                            <span class="text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded-md text-xs" x-text="' Tanggal Perolehan: ' + formatDate(item.tgl_perolehan)"></span>
                                        </template>
                                    </div>
                                </td>

                                {{-- Merk / Tipe --}}
                                <td class="py-3.5 px-4">
                                    <div class="font-medium text-gray-900" x-text="item.merk || '-'"></div>
                                    <template x-if="item.tipe && item.tipe !== 'Tanpa Tipe'">
                                        <div class="text-xs text-gray-400 mt-0.5" x-text="item.tipe"></div>
                                    </template>
                                </td>

                                {{-- NUP --}}
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-block rounded-md border border-slate-200 bg-slate-100/80 px-2.5 py-1 text-xs font-mono font-semibold text-slate-700"
                                          x-text="item.nup || '-'">
                                    </span>
                                </td>

                                {{-- Nilai Perolehan --}}
                                <td class="py-3.5 px-4 text-right">
                                    <span class="font-mono text-xs font-medium"
                                          :class="item.nilai_perolehan > 0 ? 'text-gray-900' : 'text-gray-400'"
                                          x-text="formatCurrency(item.nilai_perolehan)">
                                    </span>
                                </td>

                                {{-- Kondisi --}}
                                <td class="py-3.5 px-4 text-center">
                                    <template x-if="kondisiKunci(item) === 'baik'">
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700"
                                              x-text="kondisiMap.baik.label"></span>
                                    </template>
                                    <template x-if="kondisiKunci(item) === 'rusak_ringan'">
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700"
                                              x-text="kondisiMap.rusak_ringan.label"></span>
                                    </template>
                                    <template x-if="kondisiKunci(item) === 'rusak_berat'">
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700"
                                              x-text="kondisiMap.rusak_berat.label"></span>
                                    </template>
                                    <template x-if="kondisiKunci(item) === null">
                                        <span class="text-xs text-gray-500 font-medium" x-text="item.kondisi_text || '-'"></span>
                                    </template>
                                </td>

                            </tr>
                        </template>

                        {{-- Empty Search Filter State --}}
                        <tr x-show="filteredItems.length === 0" x-cloak>
                            <td colspan="6" class="p-12 text-center text-gray-500">
                                <div class="w-16 h-16 rounded-2xl bg-gray-50 text-gray-300 mx-auto flex items-center justify-center text-3xl mb-3 border border-gray-100">
                                    <i class="fa-solid fa-box-open"></i>
                                </div>
                                <p class="font-semibold text-gray-700 text-sm">Tidak ada inventaris yang cocok</p>
                                <p class="text-xs text-gray-400 mt-1">Gunakan kata kunci lain atau ubah pengaturan filter kondisi.</p>
                                <button type="button"
                                        @click="search = ''; filterKondisi = 'all'; currentPage = 1;"
                                        class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-800">
                                    <i class="fa-solid fa-rotate-left text-[10px]"></i> Reset Pencarian
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Table Pagination Footer --}}
            <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                 x-show="filteredItems.length > 0">
                
                {{-- Left: Pagination Range Info & Page Size Selector --}}
                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500">
                    <div>
                        Menampilkan <strong class="text-gray-900 font-mono" x-text="startRecord"></strong>
                        sampai <strong class="text-gray-900 font-mono" x-text="endRecord"></strong>
                        dari <strong class="text-gray-900 font-mono" x-text="filteredItems.length"></strong> barang
                        <span x-show="totalPages > 1">
                            (Halaman <strong class="text-gray-900 font-mono" x-text="currentPage"></strong>/<span x-text="totalPages"></span>)
                        </span>
                    </div>

                    <div class="flex items-center gap-2 border-l border-gray-200 pl-4">
                        <label for="pageSizeSelect" class="text-xs text-gray-500">Baris:</label>
                        <select id="pageSizeSelect"
                                x-model="pageSize"
                                @change="currentPage = 1"
                                class="py-1 px-2 bg-white border border-gray-200 rounded-lg text-xs font-semibold text-gray-700 focus:outline-none focus:border-blue-500 cursor-pointer">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="all">Semua</option>
                        </select>
                    </div>
                </div>

                {{-- Right: Pagination Buttons --}}
                <div class="flex items-center gap-1 self-center sm:self-auto" x-show="totalPages > 1">
                    {{-- Previous Button --}}
                    <button type="button"
                            @click="changePage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-100 hover:text-gray-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors text-xs">
                        <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    </button>

                    {{-- Page Numbers --}}
                    <template x-for="(p, i) in paginationRange" :key="i">
                        <div>
                            <template x-if="p === '...'">
                                <span class="inline-flex items-center justify-center w-8 h-8 text-xs text-gray-400">...</span>
                            </template>
                            <template x-if="p !== '...'">
                                <button type="button"
                                        @click="changePage(p)"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-semibold transition-colors"
                                        :class="currentPage === p
                                            ? 'bg-blue-600 text-white shadow-xs font-bold'
                                            : 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-100'">
                                    <span x-text="p"></span>
                                </button>
                            </template>
                        </div>
                    </template>

                    {{-- Next Button --}}
                    <button type="button"
                            @click="changePage(currentPage + 1)"
                            :disabled="currentPage === totalPages"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-100 hover:text-gray-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors text-xs">
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </button>
                </div>

            </div>

        </div>

    </div>
</x-layout>
