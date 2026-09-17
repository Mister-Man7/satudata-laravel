<x-layout>
    <x-slot:title>
        {{ $title ?? 'Profil Dosen' }}
    </x-slot:title>

    <section class="mx-auto max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
        @php
            $isFromPegawai = request()->is('pegawai*') || url()->previous() == route('pegawai.profil-dosen');
            $backUrl = $isFromPegawai 
                ? route('pegawai.profil-dosen') 
                : route('akademik.perkuliahan', ['semester' => $semester]);
        @endphp

        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 uppercase">Profil Dosen</h1>
                <nav class="mt-2 flex items-center gap-2 text-sm text-gray-500">
                    <a href="/" class="hover:text-gray-700">Dashboard</a>
                    <span>/</span>
                    @if ($isFromPegawai)
                        <a href="{{ route('pegawai') }}" class="hover:text-gray-700">Pegawai</a>
                        <span>/</span>
                        <a href="{{ route('pegawai.profil-dosen') }}" class="hover:text-gray-700">Profil Dosen</a>
                    @else
                        <a href="/akademik/perkuliahan" class="hover:text-gray-700">Perkuliahan</a>
                        <span>/</span>
                        <a href="{{ route('akademik.perkuliahan', ['semester' => $semester]) }}" class="hover:text-gray-700">Monitoring Perkuliahan</a>
                    @endif
                    <span>/</span>
                    <span class="text-gray-700">{{ $profile['nama'] ?? '-' }}</span>
                </nav>
            </div>
            <a href="{{ $backUrl }}"
                class="inline-flex items-center text-[#4B00FF] hover:text-violet-800 font-semibold text-sm transition-colors self-start">
                <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>

        <!-- Top Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2 rounded-2xl border border-gray-100 bg-white shadow-sm p-6 md:p-8">
                <div class="flex flex-col md:flex-row items-start gap-6">
                    <div class="relative shrink-0 mx-auto md:mx-0">
                        <div class="w-28 h-28 md:w-32 md:h-32 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-4xl font-bold shadow-lg">
                            {{ strtoupper(substr($profile['nama'] ?? 'D', 0, 1)) }}
                        </div>
                        <div class="absolute -bottom-2 -right-2 bg-teal-700 text-white p-1.5 rounded-xl shadow-md border-2 border-white">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                    </div>
                    <div class="flex-1 w-full text-center md:text-left">
                        <h2 class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">{{ $profile['nama'] ?? '-' }}</h2>
                        <div class="mt-3 space-y-2 text-sm md:text-base text-slate-600">
                            <div class="flex items-center justify-center md:justify-start gap-2.5">
                                <i class="fa-solid fa-id-card text-slate-400 w-5"></i>
                                <span>NIP: <strong class="font-semibold text-slate-700">{{ $profile['nip'] ?? '-' }}</strong></span>
                            </div>
                            <div class="flex items-center justify-center md:justify-start gap-2.5">
                                <i class="fa-solid fa-briefcase text-slate-400 w-5"></i>
                                <span>{{ $profile['jabatan'] ?? '-' }} &mdash; {{ $profile['pangkat'] ?? '-' }}</span>
                            </div>
                            <div class="flex items-center justify-center md:justify-start gap-2.5">
                                <i class="fa-solid fa-building text-slate-400 w-5"></i>
                                <span>{{ $profile['unit_kerja'] ?? '-' }}</span>
                            </div>
                            <div class="flex items-center justify-center md:justify-start gap-2.5">
                                <i class="fa-solid fa-envelope text-slate-400 w-5"></i>
                                <span>{{ $profile['email'] ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Jadwal Hari Ini -->
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-1">Jadwal Hari Ini</h3>
                <p class="text-sm text-gray-500 mb-4">{{ now()->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</p>
                @forelse ($jadwalHariIni as $jadwal)
                    <div class="mb-3 last:mb-0 p-3 rounded-xl bg-gray-50 border border-gray-100">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-semibold text-gray-800 text-sm">{{ $jadwal['nama_mk'] }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">Kls {{ $jadwal['kelas'] }}</div>
                            </div>
                            @php
                                $statusBadge = str_contains(strtolower($jadwal['status'] ?? ''), 'terlaksana') ? 'bg-green-500 text-white' : 'bg-red-500 text-white';
                            @endphp
                            <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold {{ $statusBadge }}">{{ $jadwal['status'] ?? 'Terjadwal' }}</span>
                        </div>
                        <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                            <span class="inline-flex items-center gap-1"><i class="fa-regular fa-clock"></i> {{ $jadwal['jam'] }} WIB</span>
                            <span class="inline-flex items-center gap-1"><i class="fa-solid fa-location-dot"></i> {{ $jadwal['ruang'] }}</span>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center gap-2 py-6 text-gray-400">
                        <i class="fa-regular fa-calendar-xmark text-3xl"></i>
                        <span class="text-sm">Tidak ada jadwal hari ini</span>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6">
                <h4 class="text-sm font-bold text-gray-700 mb-1">Jumlah SKS Mengajar</h4>
                <p class="text-xs text-gray-500 mb-4">Semester {{ $semester }}</p>
                <div class="flex items-end justify-center gap-6 h-40">
                    <div class="flex flex-col items-center gap-2">
                        <span class="text-xs font-bold text-blue-600">{{ $statistikMengajar['total_sks'] }}</span>
                        <div class="w-16 bg-gradient-to-t from-blue-600 to-blue-400 rounded-t-lg" style="height: {{ min(120, max(8, $statistikMengajar['total_sks'] * 5)) }}px"></div>
                        <span class="text-xs text-gray-500">SKS</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <span class="text-xs font-bold text-indigo-600">{{ $statistikMengajar['total_mk'] }}</span>
                        <div class="w-16 bg-gradient-to-t from-indigo-600 to-indigo-400 rounded-t-lg" style="height: {{ min(120, max(8, $statistikMengajar['total_mk'] * 12)) }}px"></div>
                        <span class="text-xs text-gray-500">MK</span>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6">
                <h4 class="text-sm font-bold text-gray-700 mb-1">Bimbingan Akademik Aktif</h4>
                <p class="text-xs text-gray-500 mb-4">Mahasiswa Perwalian Aktif</p>
                <div class="flex items-end justify-center gap-4 h-40">
                    <div class="flex flex-col items-center gap-2"><span class="text-xs font-bold text-gray-400">-</span><div class="w-12 bg-gray-200 rounded-t-lg" style="height:4px"></div><span class="text-xs text-gray-500">D3</span></div>
                    <div class="flex flex-col items-center gap-2"><span class="text-xs font-bold text-gray-400">-</span><div class="w-12 bg-gray-200 rounded-t-lg" style="height:4px"></div><span class="text-xs text-gray-500">S1</span></div>
                    <div class="flex flex-col items-center gap-2"><span class="text-xs font-bold text-gray-400">-</span><div class="w-12 bg-gray-200 rounded-t-lg" style="height:4px"></div><span class="text-xs text-gray-500">S2</span></div>
                    <div class="flex flex-col items-center gap-2"><span class="text-xs font-bold text-gray-400">-</span><div class="w-12 bg-gray-200 rounded-t-lg" style="height:4px"></div><span class="text-xs text-gray-500">S3</span></div>
                </div>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6">
                <h4 class="text-sm font-bold text-gray-700 mb-1">Bimbingan Tugas Akhir Aktif</h4>
                <p class="text-xs text-gray-500 mb-4">Berdasarkan Jenjang</p>
                <div class="flex items-end justify-center gap-4 h-40">
                    <div class="flex flex-col items-center gap-2"><span class="text-xs font-bold text-gray-400">-</span><div class="w-12 bg-gray-200 rounded-t-lg" style="height:4px"></div><span class="text-xs text-gray-500">D3</span></div>
                    <div class="flex flex-col items-center gap-2"><span class="text-xs font-bold text-gray-400">-</span><div class="w-12 bg-gray-200 rounded-t-lg" style="height:4px"></div><span class="text-xs text-gray-500">S1</span></div>
                    <div class="flex flex-col items-center gap-2"><span class="text-xs font-bold text-gray-400">-</span><div class="w-12 bg-gray-200 rounded-t-lg" style="height:4px"></div><span class="text-xs text-gray-500">S2</span></div>
                    <div class="flex flex-col items-center gap-2"><span class="text-xs font-bold text-gray-400">-</span><div class="w-12 bg-gray-200 rounded-t-lg" style="height:4px"></div><span class="text-xs text-gray-500">S3</span></div>
                </div>
            </div>
        </div>

        <!-- Tri Dharma Section (Disatukan dengan Ringkasan Luaran Ilmiah) -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Tri Dharma Perguruan Tinggi</h3>
                <span class="text-xs text-gray-500">Semester {{ $semester }}</span>
            </div>

            <!-- Indeksasi Eksternal (SINTA, Scopus, Google Scholar) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="text-center p-3 rounded-xl bg-blue-50 border border-blue-100">
                    <div class="text-2xl font-bold text-blue-600">{{ $sintaIndexasi['scopus']['dokumen'] ?? '-' }}</div>
                    <div class="text-xs text-gray-500 mt-1 font-medium uppercase tracking-wide">Skor SINTA</div>
                </div>
                <div class="text-center p-3 rounded-xl bg-amber-50 border border-amber-100">
                    <div class="text-2xl font-bold text-amber-600">{{ $sintaIndexasi['scopus']['h_index'] ?? '-' }}</div>
                    <div class="text-xs text-gray-500 mt-1 font-medium uppercase tracking-wide">Scopus H-Index</div>
                </div>
                <div class="text-center p-3 rounded-xl bg-emerald-50 border border-emerald-100">
                    <div class="text-2xl font-bold text-emerald-600">{{ $sintaIndexasi['google_scholar']['h_index'] ?? '-' }}</div>
                    <div class="text-xs text-gray-500 mt-1 font-medium uppercase tracking-wide">G Scholar H-Index</div>
                </div>
            </div>

            <!-- Indikator Luaran Ilmiah & Penelitian (SIPP) -->
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 mb-4 border-b border-gray-100 gap-2">
                    <div>
                        <h4 class="text-base font-bold text-gray-800 flex items-center gap-2">
                            <i class="fa-solid fa-chart-simple text-[#4B00FF]"></i> Indikator Luaran Ilmiah & Penelitian (SIPP)
                        </h4>
                        <p class="text-xs text-gray-500 mt-0.5">Beban kinerja luaran Tri Dharma berdasarkan integrasi Sistem Informasi Penelitian dan Pengabdian</p>
                    </div>
                    <span id="sipp-status-badge" class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 self-start sm:self-auto transition-colors">
                        <i class="fa-solid fa-spinner fa-spin text-[11px] text-indigo-600"></i>
                        <span>Memuat data SIPP...</span>
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
                    <div class="text-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div id="metric-sinta12" class="text-xl font-bold text-blue-600 min-h-[28px] flex items-center justify-center">
                            <i class="fa-solid fa-spinner fa-spin text-sm text-slate-300"></i>
                        </div>
                        <div class="text-[11px] text-gray-600 mt-1 font-medium">SINTA 1–2</div>
                    </div>
                    <div class="text-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div id="metric-sinta36" class="text-xl font-bold text-cyan-600 min-h-[28px] flex items-center justify-center">
                            <i class="fa-solid fa-spinner fa-spin text-sm text-slate-300"></i>
                        </div>
                        <div class="text-[11px] text-gray-600 mt-1 font-medium">SINTA 3–6</div>
                    </div>
                    <div class="text-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div id="metric-jurnal_internasional_q" class="text-xl font-bold text-indigo-600 min-h-[28px] flex items-center justify-center">
                            <i class="fa-solid fa-spinner fa-spin text-sm text-slate-300"></i>
                        </div>
                        <div class="text-[11px] text-gray-600 mt-1 font-medium">Int. Q1–Q4</div>
                    </div>
                    <div class="text-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div id="metric-jurnal_internasional_pbb" class="text-xl font-bold text-violet-600 min-h-[28px] flex items-center justify-center">
                            <i class="fa-solid fa-spinner fa-spin text-sm text-slate-300"></i>
                        </div>
                        <div class="text-[11px] text-gray-600 mt-1 font-medium">Int. PBB</div>
                    </div>
                    <div class="text-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div id="metric-jurnal_nasional_issn" class="text-xl font-bold text-teal-600 min-h-[28px] flex items-center justify-center">
                            <i class="fa-solid fa-spinner fa-spin text-sm text-slate-300"></i>
                        </div>
                        <div class="text-[11px] text-gray-600 mt-1 font-medium">Nasional ISSN</div>
                    </div>
                    <div class="text-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div id="metric-buku_referensi" class="text-xl font-bold text-amber-600 min-h-[28px] flex items-center justify-center">
                            <i class="fa-solid fa-spinner fa-spin text-sm text-slate-300"></i>
                        </div>
                        <div class="text-[11px] text-gray-600 mt-1 font-medium">Buku Referensi</div>
                    </div>
                    <div class="text-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div id="metric-pengembangan" class="text-xl font-bold text-orange-600 min-h-[28px] flex items-center justify-center">
                            <i class="fa-solid fa-spinner fa-spin text-sm text-slate-300"></i>
                        </div>
                        <div class="text-[11px] text-gray-600 mt-1 font-medium">Pengembangan</div>
                    </div>
                    <div class="text-center p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <div id="metric-pengabdian_masyarakat" class="text-xl font-bold text-emerald-600 min-h-[28px] flex items-center justify-center">
                            <i class="fa-solid fa-spinner fa-spin text-sm text-slate-300"></i>
                        </div>
                        <div class="text-[11px] text-gray-600 mt-1 font-medium">Pengabdian</div>
                    </div>
                </div>
            </div>

            <!-- Detail 3 Kolom Tri Dharma: Publikasi, Penelitian, Pengabdian -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Publikasi Ilmiah -->
                <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
                            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <i class="fa-solid fa-book-bookmark text-indigo-500"></i> Publikasi
                            </h3>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700">
                                {{ count($publikasi10Tahun) }} Item
                            </span>
                        </div>
                        <div id="list-publikasi" class="space-y-3">
                            @forelse ($publikasi10Tahun as $pub)
                                <div class="tridarma-item-publikasi p-3.5 rounded-xl bg-slate-50 border border-slate-100 hover:border-indigo-200 transition-colors">
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="font-semibold text-slate-800 text-sm leading-snug line-clamp-2">{{ $pub['judul'] }}</h4>
                                        <span class="shrink-0 px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-100 text-indigo-700">
                                            {{ $pub['tahun'] }}
                                        </span>
                                    </div>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                        @if (!empty($pub['tipe']) && $pub['tipe'] !== '-')
                                            <span class="inline-flex items-center gap-1 font-medium text-indigo-600">
                                                <i class="fa-solid fa-tag text-[10px]"></i> {{ $pub['tipe'] }}
                                            </span>
                                        @endif
                                        @if (!empty($pub['journal']) && $pub['journal'] !== '-')
                                            <span class="truncate max-w-[200px] text-slate-500">
                                                <i class="fa-regular fa-newspaper"></i> {{ $pub['journal'] }}
                                            </span>
                                        @endif
                                    </div>
                                    @if (!empty($pub['doi_url']) || !empty($pub['tautan']))
                                        <div class="mt-2.5 pt-2 border-t border-slate-200/60 flex items-center justify-between">
                                            @if (!empty($pub['doi_url']))
                                                <a href="{{ $pub['doi_url'] }}" target="_blank" rel="noopener noreferrer"
                                                   class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 text-indigo-600 hover:bg-indigo-100 hover:text-indigo-800 transition">
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                                    <span>DOI</span>
                                                </a>
                                            @elseif (!empty($pub['tautan']))
                                                <a href="{{ $pub['tautan'] }}" target="_blank" rel="noopener noreferrer"
                                                   class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600 hover:bg-indigo-50 hover:text-indigo-700 transition">
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                                    <span>Tautan</span>
                                                </a>
                                            @endif
                                            @if (!empty($pub['status_verifikasi']) && $pub['status_verifikasi'] !== '-')
                                                @php
                                                    $isVerified = str_contains(strtolower($pub['status_verifikasi']), 'terverifikasi') && !str_contains(strtolower($pub['status_verifikasi']), 'belum');
                                                @endphp
                                                <span class="inline-flex items-center gap-1 font-semibold text-[10px] {{ $isVerified ? 'text-emerald-600' : 'text-amber-600' }}">
                                                    <i class="fa-solid {{ $isVerified ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
                                                    {{ $pub['status_verifikasi'] }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-8 text-slate-400">
                                    <i class="fa-regular fa-folder-open text-3xl mb-2 text-slate-300"></i>
                                    <span class="text-sm">Belum ada data publikasi</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <!-- Pagination Publikasi -->
                    <div id="pagination-publikasi" class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span id="info-publikasi">Menampilkan data</span>
                        <div class="flex items-center gap-1">
                            <button type="button" id="prev-publikasi" aria-label="Halaman Sebelumnya" class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-30 disabled:pointer-events-none transition-colors">
                                <i class="fa-solid fa-chevron-left text-[10px]"></i>
                            </button>
                            <div id="pages-publikasi" class="flex items-center gap-1"></div>
                            <button type="button" id="next-publikasi" aria-label="Halaman Selanjutnya" class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-30 disabled:pointer-events-none transition-colors">
                                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Penelitian -->
                <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
                            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <i class="fa-solid fa-microscope text-rose-500"></i> Penelitian
                            </h3>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700">
                                {{ count($penelitianList) }} Item
                            </span>
                        </div>
                        <div id="list-penelitian" class="space-y-3">
                            @forelse ($penelitianList as $pen)
                                <div class="tridarma-item-penelitian p-3.5 rounded-xl bg-slate-50 border border-slate-100 hover:border-rose-200 transition-colors">
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="font-semibold text-slate-800 text-sm leading-snug line-clamp-2">{{ $pen['judul'] }}</h4>
                                        <span class="shrink-0 px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-700">
                                            {{ $pen['tahun'] }}
                                        </span>
                                    </div>
                                    @if (!empty($pen['tipe']) && $pen['tipe'] !== '-')
                                        <p class="mt-1.5 text-xs text-slate-600 line-clamp-1">
                                            <i class="fa-solid fa-layer-group text-[10px] text-slate-400"></i> {{ $pen['tipe'] }}
                                        </p>
                                    @endif
                                    <div class="mt-2 flex items-center justify-between text-xs">
                                        @if (!empty($pen['lokasi']) && $pen['lokasi'] !== '-')
                                            <span class="text-slate-500 truncate max-w-[140px]">
                                                <i class="fa-solid fa-location-dot text-slate-400"></i> {{ $pen['lokasi'] }}
                                            </span>
                                        @else
                                            <span></span>
                                        @endif
                                        <div class="flex items-center gap-2">
                                            @if (!empty($pen['doi_url']))
                                                <a href="{{ $pen['doi_url'] }}" target="_blank" rel="noopener noreferrer"
                                                   class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-800 transition">
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i> DOI
                                                </a>
                                            @elseif (!empty($pen['tautan']))
                                                <a href="{{ $pen['tautan'] }}" target="_blank" rel="noopener noreferrer"
                                                   class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 hover:bg-rose-50 hover:text-rose-700 transition">
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i> Tautan
                                                </a>
                                            @endif
                                            @php
                                                $statusLabel = $pen['status_verifikasi'] ?? '-';
                                                $isVerified = str_contains(strtolower($statusLabel), 'terverifikasi') && !str_contains(strtolower($statusLabel), 'belum');
                                            @endphp
                                            <span class="inline-flex items-center gap-1 font-semibold text-[10px] {{ $isVerified ? 'text-emerald-600' : 'text-amber-600' }}">
                                                <i class="fa-solid {{ $isVerified ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
                                                {{ $statusLabel }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-8 text-slate-400">
                                    <i class="fa-regular fa-folder-open text-3xl mb-2 text-slate-300"></i>
                                    <span class="text-sm">Belum ada data penelitian</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <!-- Pagination Penelitian -->
                    <div id="pagination-penelitian" class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span id="info-penelitian">Menampilkan data</span>
                        <div class="flex items-center gap-1">
                            <button type="button" id="prev-penelitian" aria-label="Halaman Sebelumnya" class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-30 disabled:pointer-events-none transition-colors">
                                <i class="fa-solid fa-chevron-left text-[10px]"></i>
                            </button>
                            <div id="pages-penelitian" class="flex items-center gap-1"></div>
                            <button type="button" id="next-penelitian" aria-label="Halaman Selanjutnya" class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-30 disabled:pointer-events-none transition-colors">
                                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pengabdian -->
                <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
                            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <i class="fa-solid fa-handshake-angle text-emerald-500"></i> Pengabdian Kepada Masyarakat
                            </h3>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700">
                                {{ count($pengabdianList) }} Item
                            </span>
                        </div>
                        <div id="list-pengabdian" class="space-y-3">
                            @forelse ($pengabdianList as $peng)
                                <div class="tridarma-item-pengabdian p-3.5 rounded-xl bg-slate-50 border border-slate-100 hover:border-emerald-200 transition-colors">
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="font-semibold text-slate-800 text-sm leading-snug line-clamp-2">{{ $peng['judul'] }}</h4>
                                        <span class="shrink-0 px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-700">
                                            {{ $peng['tahun'] }}
                                        </span>
                                    </div>
                                    @if (!empty($peng['tipe']) && $peng['tipe'] !== '-')
                                        <p class="mt-1.5 text-xs text-slate-600 line-clamp-1">
                                            <i class="fa-solid fa-layer-group text-[10px] text-slate-400"></i> {{ $peng['tipe'] }}
                                        </p>
                                    @endif
                                    <div class="mt-2 flex items-center justify-between text-xs">
                                        @if (!empty($peng['lokasi']) && $peng['lokasi'] !== '-')
                                            <span class="text-slate-500 truncate max-w-[140px]">
                                                <i class="fa-solid fa-location-dot text-slate-400"></i> {{ $peng['lokasi'] }}
                                            </span>
                                        @else
                                            <span></span>
                                        @endif
                                        <div class="flex items-center gap-2">
                                            @if (!empty($peng['doi_url']))
                                                <a href="{{ $peng['doi_url'] }}" target="_blank" rel="noopener noreferrer"
                                                   class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:text-emerald-800 transition">
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i> DOI
                                                </a>
                                            @elseif (!empty($peng['tautan']))
                                                <a href="{{ $peng['tautan'] }}" target="_blank" rel="noopener noreferrer"
                                                   class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 hover:bg-emerald-50 hover:text-emerald-700 transition">
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i> Tautan
                                                </a>
                                            @endif
                                            @php
                                                $statusLabel = $peng['status_verifikasi'] ?? '-';
                                                $isVerified = str_contains(strtolower($statusLabel), 'terverifikasi') && !str_contains(strtolower($statusLabel), 'belum');
                                            @endphp
                                            <span class="inline-flex items-center gap-1 font-semibold text-[10px] {{ $isVerified ? 'text-emerald-600' : 'text-amber-600' }}">
                                                <i class="fa-solid {{ $isVerified ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
                                                {{ $statusLabel }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-8 text-slate-400">
                                    <i class="fa-regular fa-folder-open text-3xl mb-2 text-slate-300"></i>
                                    <span class="text-sm">Belum ada data pengabdian</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <!-- Pagination Pengabdian -->
                    <div id="pagination-pengabdian" class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span id="info-pengabdian">Menampilkan data</span>
                        <div class="flex items-center gap-1">
                            <button type="button" id="prev-pengabdian" aria-label="Halaman Sebelumnya" class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-30 disabled:pointer-events-none transition-colors">
                                <i class="fa-solid fa-chevron-left text-[10px]"></i>
                            </button>
                            <div id="pages-pengabdian" class="flex items-center gap-1"></div>
                            <button type="button" id="next-pengabdian" aria-label="Halaman Selanjutnya" class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-30 disabled:pointer-events-none transition-colors">
                                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const nip = @json($nip);
            const semester = @json($semester);
            const url = "{{ route('pegawai.profil-dosen.sipp-metrics', ['nip' => ':nip']) }}".replace(':nip', encodeURIComponent(nip)) + '?semester=' + encodeURIComponent(semester);

            fetch(url)
                .then(response => response.json())
                .then(res => {
                    if (res && res.success && res.metrics) {
                        const m = res.metrics;
                        const updateMetric = (id, val) => {
                            const el = document.getElementById(id);
                            if (el) {
                                el.textContent = val ?? 0;
                            }
                        };

                        updateMetric('metric-sinta12', m.sinta12);
                        updateMetric('metric-sinta36', m.sinta36);
                        updateMetric('metric-jurnal_internasional_q', m.jurnal_internasional_q);
                        updateMetric('metric-jurnal_internasional_pbb', m.jurnal_internasional_pbb);
                        updateMetric('metric-jurnal_nasional_issn', m.jurnal_nasional_issn);
                        updateMetric('metric-buku_referensi', m.buku_referensi);
                        updateMetric('metric-pengembangan', m.pengembangan);
                        updateMetric('metric-pengabdian_masyarakat', m.pengabdian_masyarakat);

                        const badge = document.getElementById('sipp-status-badge');
                        if (badge) {
                            badge.innerHTML = '<i class="fa-regular fa-calendar text-[10px]"></i> Semester ' + (res.semester || semester);
                            badge.className = 'inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 self-start sm:self-auto transition-colors';
                        }
                    } else {
                        ['metric-sinta12', 'metric-sinta36', 'metric-jurnal_internasional_q', 'metric-jurnal_internasional_pbb', 'metric-jurnal_nasional_issn', 'metric-buku_referensi', 'metric-pengembangan', 'metric-pengabdian_masyarakat'].forEach(id => {
                            const el = document.getElementById(id);
                            if (el) el.textContent = '0';
                        });
                        const badge = document.getElementById('sipp-status-badge');
                        if (badge) {
                            badge.innerHTML = '<i class="fa-regular fa-calendar text-[10px]"></i> Semester ' + semester;
                            badge.className = 'inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 self-start sm:self-auto transition-colors';
                        }
                    }
                })
                .catch(err => {
                    console.error('Gagal mengambil data SIPP:', err);
                    ['metric-sinta12', 'metric-sinta36', 'metric-jurnal_internasional_q', 'metric-jurnal_internasional_pbb', 'metric-jurnal_nasional_issn', 'metric-buku_referensi', 'metric-pengembangan', 'metric-pengabdian_masyarakat'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.textContent = '0';
                    });
                    const badge = document.getElementById('sipp-status-badge');
                    if (badge) {
                        badge.innerHTML = '<i class="fa-regular fa-calendar text-[10px]"></i> Semester ' + semester;
                        badge.className = 'inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 self-start sm:self-auto transition-colors';
                    }
                });

            // Tri Dharma Interactive Pagination (5 items per page)
            function initTridarmaPagination(category, pageSize, activeClass) {
                const listContainer = document.getElementById(`list-${category}`);
                const paginationContainer = document.getElementById(`pagination-${category}`);
                const infoEl = document.getElementById(`info-${category}`);
                const prevBtn = document.getElementById(`prev-${category}`);
                const nextBtn = document.getElementById(`next-${category}`);
                const pagesEl = document.getElementById(`pages-${category}`);

                if (!listContainer) return;
                const items = Array.from(listContainer.querySelectorAll(`.tridarma-item-${category}`));
                const totalItems = items.length;

                if (totalItems === 0) {
                    if (paginationContainer) paginationContainer.style.display = 'none';
                    return;
                }

                const totalPages = Math.ceil(totalItems / pageSize);
                if (totalPages <= 1) {
                    if (paginationContainer) {
                        paginationContainer.style.display = 'flex';
                        if (infoEl) infoEl.textContent = `Total ${totalItems} item`;
                        if (prevBtn) prevBtn.style.display = 'none';
                        if (nextBtn) nextBtn.style.display = 'none';
                        if (pagesEl) pagesEl.style.display = 'none';
                    }
                    return;
                }

                let currentPage = 1;

                function renderPage(page) {
                    currentPage = page;
                    const start = (page - 1) * pageSize;
                    const end = Math.min(start + pageSize, totalItems);

                    items.forEach((item, index) => {
                        if (index >= start && index < end) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    if (infoEl) {
                        infoEl.textContent = `${start + 1}–${end} dari ${totalItems}`;
                    }

                    if (prevBtn) {
                        prevBtn.disabled = (currentPage === 1);
                    }
                    if (nextBtn) {
                        nextBtn.disabled = (currentPage === totalPages);
                    }

                    if (pagesEl) {
                        pagesEl.innerHTML = '';
                        let startPage = Math.max(1, currentPage - 1);
                        let endPage = Math.min(totalPages, startPage + 2);
                        if (endPage - startPage < 2) {
                            startPage = Math.max(1, endPage - 2);
                        }

                        for (let i = startPage; i <= endPage; i++) {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.textContent = i;
                            btn.className = (i === currentPage)
                                ? `w-7 h-7 rounded-lg font-bold text-xs flex items-center justify-center shadow-sm ${activeClass}`
                                : 'w-7 h-7 rounded-lg text-slate-600 hover:bg-slate-100 font-semibold text-xs flex items-center justify-center border border-slate-200 bg-white transition-colors';
                            btn.addEventListener('click', () => renderPage(i));
                            pagesEl.appendChild(btn);
                        }
                    }
                }

                if (prevBtn) {
                    prevBtn.addEventListener('click', () => {
                        if (currentPage > 1) renderPage(currentPage - 1);
                    });
                }

                if (nextBtn) {
                    nextBtn.addEventListener('click', () => {
                        if (currentPage < totalPages) renderPage(currentPage + 1);
                    });
                }

                renderPage(1);
            }

            initTridarmaPagination('publikasi', 5, 'bg-indigo-600 text-white');
            initTridarmaPagination('penelitian', 5, 'bg-rose-600 text-white');
            initTridarmaPagination('pengabdian', 5, 'bg-emerald-600 text-white');
        });
    </script>
</x-layout>
