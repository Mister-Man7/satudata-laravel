<x-layout>
    <x-slot:title>
        {{ $title ?? 'Profil Dosen' }}
    </x-slot:title>
    <section class="mx-auto max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 uppercase">Profil Dosen</h1>
                <nav class="mt-2 flex items-center gap-2 text-sm text-gray-500">
                    <a href="/" class="hover:text-gray-700">Dashboard</a>
                    <span>/</span>
                    <a href="/akademik/perkuliahan" class="hover:text-gray-700">Perkuliahan</a>
                    <span>/</span>
                    <a href="/akademik/perkuliahan" class="hover:text-gray-700">Monitoring Perkuliahan</a>
                    <span>/</span>
                    <span class="text-gray-700">{{ $profile['nama'] ?? '-' }}</span>
                </nav>
            </div>
            <a href="{{ route('akademik.perkuliahan', ['semester' => $semester]) }}"
                class="inline-flex items-center gap-2 rounded-lg bg-teal-500 px-4 py-2 text-sm font-medium text-white hover:bg-teal-600 self-start">
                <i class="fa-solid fa-arrow-left"></i> Kembali
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
                        <div class="mt-6 grid grid-cols-3 gap-4">
                            <div class="text-center p-3 rounded-xl bg-blue-50 border border-blue-100">
                                <div class="text-2xl font-bold text-blue-600">{{ $sintaIndexasi['scopus']['dokumen'] ?? 0 }}</div>
                                <div class="text-xs text-gray-500 mt-1 font-medium uppercase tracking-wide">Skor SINTA</div>
                            </div>
                            <div class="text-center p-3 rounded-xl bg-amber-50 border border-amber-100">
                                <div class="text-2xl font-bold text-amber-600">{{ $sintaIndexasi['scopus']['h_index'] ?? 0 }}</div>
                                <div class="text-xs text-gray-500 mt-1 font-medium uppercase tracking-wide">Scopus H-Index</div>
                            </div>
                            <div class="text-center p-3 rounded-xl bg-emerald-50 border border-emerald-100">
                                <div class="text-2xl font-bold text-emerald-600">{{ $sintaIndexasi['google_scholar']['h_index'] ?? 0 }}</div>
                                <div class="text-xs text-gray-500 mt-1 font-medium uppercase tracking-wide">G Scholar H-Index</div>
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
                                $statusBadge = str_contains(strtolower($jadwal['status']), 'terlaksana') ? 'bg-green-500 text-white' : 'bg-red-500 text-white';
                            @endphp
                            <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold {{ $statusBadge }}">{{ $jadwal['status'] }}</span>
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

        <!-- Education + Penelitian + Pengabdian -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-graduation-cap text-blue-500"></i> Education
                </h3>
                <div class="space-y-4">
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <div class="w-3 h-3 rounded-full bg-blue-500 mt-1"></div>
                            <div class="w-0.5 flex-1 bg-blue-200"></div>
                        </div>
                        <div class="pb-4">
                            <div class="font-bold text-gray-800">Data tidak tersedia</div>
                            <div class="text-sm text-gray-500">Informasi pendidikan belum terhubung dari sistem</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="space-y-6">
                <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-microscope text-rose-500"></i> Penelitian
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        @forelse ($penelitianList as $pen)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200/60">
                                {{ $pen['judul'] }}
                            </span>
                        @empty
                            <span class="text-sm text-gray-400 italic">Belum ada data penelitian</span>
                        @endforelse
                    </div>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-white shadow-sm p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-handshake-angle text-emerald-500"></i> Pengabdian
                    </h3>
                    <p class="text-sm text-gray-500">Data pengabdian belum tersedia dari sistem.</p>
                </div>
            </div>
        </div>

    </section>
</x-layout>
