<x-layout>
    <x-slot:title>
        {{ $title ?? 'Profil Dosen' }}
    </x-slot:title>

    <section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <nav class="mb-2.5 flex items-center gap-2 text-xs font-medium text-gray-400">
                    <a href="/" class="hover:text-gray-600 transition">Dashboard</a>
                    <span>/</span>
                    <a href="{{ route('pegawai') }}" class="hover:text-gray-600 transition">Pegawai</a>
                    <span>/</span>
                    <span class="text-indigo-600 font-semibold">Profil Dosen</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                    Profil Dosen UNTIRTA
                </h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1.5">
                    Daftar profil tenaga pendidik (Dosen) terintegrasi SIMPEG &amp; SIPP Untirta.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('pegawai') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 transition shadow-sm">
                    <i class="fa-solid fa-chart-pie text-indigo-600"></i>
                    Statistik Pegawai
                </a>
            </div>
        </div>

        <!-- Summary Stat Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-5 mb-8">
            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Total Dosen</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($totalDosen, 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-award"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Guru Besar</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($totalGuruBesar, 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Lektor Kepala</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($totalLektorKepala, 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid fa-book-open"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Lektor</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($totalLektor, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <!-- Faculty Tabs / Quick Filter Card with Generous Gap & Padding -->
        <div class="mb-8 bg-white rounded-2xl border border-gray-100 p-6 sm:p-7 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-5 mb-5 border-b border-gray-100 gap-3.5">
                <div class="flex items-center gap-3.5">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Filter Berdasarkan Fakultas / Unit</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Pilih fakultas untuk menyaring daftar dosen terdaftar</p>
                    </div>
                </div>
                @if (!empty($selectedFakultas))
                    <a href="{{ route('pegawai.profil-dosen', request()->except(['fakultas', 'page'])) }}"
                       class="inline-flex items-center gap-1 p-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 transition self-start sm:self-auto border border-rose-100">
                        <i class="fa-solid fa-xmark text-xs"></i>
                        <span>Reset Filter Fakultas</span>
                    </a>
                @endif
            </div>

            <!-- Wrapped Filter Pills with Generous Gap and Padding -->
            <div class="flex flex-wrap items-center gap-3 sm:gap-3.5 pt-1.5">
                <!-- Tab: Semua Fakultas -->
                @php
                    $isAll = empty($selectedFakultas);
                    $allUrl = route('pegawai.profil-dosen', array_merge(request()->except(['fakultas', 'page'])));
                @endphp
                <a href="{{ $allUrl }}"
                   class="inline-flex items-center gap-2.5 px-4 py-2.5 sm:px-5 sm:py-3 rounded-xl text-xs font-semibold transition shadow-sm {{ $isAll ? 'bg-indigo-600 text-white shadow-indigo-200 border border-indigo-600' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200/90 hover:border-gray-300' }}">
                    <i class="fa-solid fa-globe text-xs {{ $isAll ? 'text-indigo-200' : 'text-gray-400' }}"></i>
                    <span>Semua Fakultas</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $isAll ? 'bg-indigo-700/90 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">
                        {{ number_format($totalDosen, 0, ',', '.') }}
                    </span>
                </a>

                @foreach ($facultyOptions as $fOpt)
                    @php
                        $isSelected = ($selectedFakultas === $fOpt);
                        $fCount = $facultyCounts[$fOpt] ?? 0;
                        $fUrl = route('pegawai.profil-dosen', array_merge(request()->except(['fakultas', 'page']), ['fakultas' => $fOpt]));
                    @endphp
                    <a href="{{ $fUrl }}"
                       class="inline-flex items-center gap-2.5 px-4 py-2.5 sm:px-5 sm:py-3 rounded-xl text-xs font-semibold transition shadow-sm {{ $isSelected ? 'bg-indigo-600 text-white shadow-indigo-200 border border-indigo-600' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200/90 hover:border-gray-300' }}">
                        <i class="fa-solid fa-building-columns text-xs {{ $isSelected ? 'text-indigo-200' : 'text-gray-400' }}"></i>
                        <span>{{ $fOpt }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $isSelected ? 'bg-indigo-700/90 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">
                            {{ $fCount }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Filter & Search Toolbar with Generous Spacing -->
        <div class="mb-8 bg-white rounded-2xl border border-gray-100 p-6 sm:p-7 shadow-sm">
            <form method="GET" action="{{ route('pegawai.profil-dosen') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 sm:gap-4.5">
                <!-- Keep selected fakultas state -->
                @if (!empty($selectedFakultas))
                    <input type="hidden" name="fakultas" value="{{ $selectedFakultas }}">
                @endif

                <!-- Search Box -->
                <div class="lg:col-span-5 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ $search ?? '' }}"
                           placeholder="Cari nama dosen, NIP, jabatan, fakultas..."
                           class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-gray-200 text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                </div>

                <!-- Filter Jabatan (Diambil Langsung Dari Kolom Data) -->
                <div class="lg:col-span-3">
                    <select name="jabatan"
                            class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition bg-white">
                        <option value="">Semua Jabatan</option>
                        @foreach ($jabatanOptions ?? [] as $jOpt)
                            <option value="{{ $jOpt }}" {{ ($selectedJabatan ?? '') === $jOpt ? 'selected' : '' }}>
                                {{ $jOpt }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Status Kerja -->
                <div class="lg:col-span-2">
                    <select name="status_kerja"
                            class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition bg-white">
                        <option value="">Semua Status</option>
                        @foreach ($statusKerjaOptions as $opt)
                            <option value="{{ $opt }}" {{ ($selectedStatusKerja ?? '') === $opt ? 'selected' : '' }}>
                                {{ $opt }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="lg:col-span-2 flex items-center gap-2.5">
                    <button type="submit"
                            class="flex-1 px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition shadow-sm flex items-center justify-center gap-2">
                        <i class="fa-solid fa-filter text-xs"></i>
                        Filter
                    </button>
                    @if (!empty($search) || !empty($selectedJabatan) || !empty($selectedStatusKerja))
                        <a href="{{ route('pegawai.profil-dosen', !empty($selectedFakultas) ? ['fakultas' => $selectedFakultas] : []) }}"
                           class="p-2.5 rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-50 transition"
                           title="Reset filter pencarian">
                            <i class="fa-solid fa-rotate-left text-xs"></i>
                        </a>
                    @endif
                </div>
            </form>

            <div class="mt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs text-gray-500 pt-4 border-t border-gray-100 gap-3">
                <div>
                    Menampilkan <strong>{{ $dosens->firstItem() ?? 0 }} - {{ $dosens->lastItem() ?? 0 }}</strong> dari <strong>{{ number_format($totalFiltered, 0, ',', '.') }}</strong> dosen
                    @if (!empty($selectedFakultas))
                        pada <span class="font-bold text-indigo-600">{{ $selectedFakultas }}</span>
                    @endif
                    @if (!empty($search) || !empty($selectedJabatan) || !empty($selectedStatusKerja))
                        (disaring dari total {{ number_format($totalDosen, 0, ',', '.') }})
                    @endif
                </div>
                <div class="text-gray-400">
                    Halaman {{ $dosens->currentPage() }} dari {{ $dosens->lastPage() }}
                </div>
            </div>
        </div>

        <!-- Single Paginated Table -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-8">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50/75 border-b border-gray-100 text-gray-500 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="px-6 py-4 w-14 text-center">No</th>
                            <th class="px-6 py-4">Nama &amp; Gelar Dosen</th>
                            <th class="px-6 py-4">NIP</th>
                            <th class="px-6 py-4">Fakultas / Unit Kerja</th>
                            <th class="px-6 py-4">Jabatan Fungsional</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse ($dosens as $idx => $d)
                            @php
                                $namaDosen = $d['nama_pegawai_lengkap'] ?? $d['nama_pegawai'] ?? $d['nama'] ?? '-';
                                $nipDosen = $d['nip'] ?? '';
                                $fakultas = $d['unitKerja'] ?? $d['unit_kerja'] ?? '-';
                                $jabatan = $d['nama_jabatan'] ?? $d['jabatan'] ?? 'Dosen Pengajar';
                                $isProf = stripos($jabatan, 'Profesor') !== false || stripos($jabatan, 'Guru Besar') !== false || stripos($namaDosen, 'Prof.') !== false;
                                $isLektorKepala = stripos($jabatan, 'Lektor Kepala') !== false;
                                $profilUrl = !empty($nipDosen) ? route('pegawai.profil-dosen.show', ['nip' => $nipDosen]) : '#';
                            @endphp
                            <tr class="hover:bg-indigo-50/20 transition">
                                <td class="px-6 py-4 text-center font-mono text-gray-400 text-xs">
                                    {{ $dosens->firstItem() + $idx }}
                                </td>
                                
                                <!-- Dosen Info -->
                                <td class="px-6 py-4">
                                    <div>
                                        @if (!empty($nipDosen))
                                            <p
                                               class="font-bold text-gray-900 hover:text-indigo-600 transition group inline-flex items-center gap-1.5"
                                               title="Lihat Profil {{ $namaDosen }}">
                                                <span class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600">{{ $namaDosen }}</span>
                                            </p>
                                        @else
                                            <span class="text-sm font-semibold text-gray-900">{{ $namaDosen }}</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- NIP -->
                                <td class="px-6 py-4.5">
                                    <div class="font-mono font-semibold text-gray-800 text-xs">{{ $nipDosen ?: '-' }}</div>
                                </td>

                                <!-- Fakultas / Unit -->
                                <td class="px-6 py-4.5">
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-[11px] font-medium bg-gray-50 text-gray-700 border border-gray-200">
                                        <i class="fa-solid fa-building-columns text-[10px] text-indigo-500"></i>
                                        <span>{{ $fakultas }}</span>
                                    </span>
                                </td>

                                <!-- Jabatan Fungsional -->
                                <td class="px-6 py-4.5">
                                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-[11px] font-semibold {{ $isProf ? 'bg-amber-50 text-amber-700 border border-amber-200' : ($isLektorKepala ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-blue-50 text-blue-700 border border-blue-200') }}">
                                        {{ $jabatan }}
                                    </span>
                                </td>

                                <!-- Status Pegawai -->
                                <td class="px-6 py-4.5">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-medium bg-gray-100 text-gray-700">
                                            {{ $d['nama_stspegawai'] ?: 'PNS' }}
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            {{ $d['nama_stspeg'] ?: 'Aktif' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4.5 text-right">
                                    @if (!empty($nipDosen))
                                        <a href="{{ $profilUrl }}"
                                           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition shadow-sm">
                                            <span>Profil &amp; SIPP</span>
                                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                        </a>
                                    @else
                                        <span class="text-gray-300">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-16 text-center text-gray-400">
                                    <div class="p-2 flex flex-col items-center justify-center gap-2">
                                        <div class="w-14 h-14 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl mb-2">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </div>
                                        <h3 class="font-bold text-gray-800 text-sm">Tidak ada profil dosen ditemukan</h3>
                                        <p class="text-xs text-gray-400 max-w-sm">
                                            Coba sesuaikan kata kunci pencarian atau reset filter fakultas dan jabatan.
                                        </p>
                                        <a href="{{ route('pegawai.profil-dosen') }}" class="inline-flex items-center gap-2 mt-3 px-4 py-1 rounded-xl bg-indigo-50 text-indigo-600 text-xs font-semibold hover:bg-indigo-100 transition">
                                            <i class="fa-solid fa-rotate-left text-xs"></i>
                                            Reset Filter
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            @if ($dosens->hasPages())
                <div class="px-6 sm:px-8 py-5 border-t border-gray-100 bg-gray-50/50">
                    {{ $dosens->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    </section>
</x-layout>
