<section class="pt-10 font-based">
    <div class="max-w-7xl mx-auto px-6">
        <div class="inline-block bg-[#F3F4F6] px-8 py-3.5 rounded-t-2xl text-blue-500 font-bold text-sm md:text-base">
            @if(request()->is('akademik*'))
                Menu Pilihan
            @elseif(request()->is('pegawai*'))
                Menu Pilihan
            @elseif(request()->is('aset*'))
                Menu Pilihan
            @else
                Menu Pilihan
            @endif
        </div>
        <div class="bg-[#F3F4F6] p-6 md:p-10 rounded-tr-2xl">
            @if(request()->is('akademik*'))
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-y-6 gap-x-6 text-xs md:text-sm text-gray-700 font-medium">
                    <a href="/akademik/perkuliahan" class="flex items-start gap-3 hover:text-blue-500 transition group">
                        <i class="fa-solid fa-location-arrow text-blue-500 mt-1 transform -rotate-45 group-hover:translate-x-1 transition"></i>
                        <span>Monitoring Perkuliahan</span>
                    </a>
                    <a href="/akademik/siakad" class="flex items-start gap-3 hover:text-blue-500 transition group">
                        <i class="fa-solid fa-location-arrow text-blue-500 mt-1 transform -rotate-45 group-hover:translate-x-1 transition"></i>
                        <span>Portal SIAKAD Mahasiswa</span>
                    </a>
                    <a href="/akademik/kalender" class="flex items-start gap-3 hover:text-blue-500 transition group">
                        <i class="fa-solid fa-location-arrow text-blue-500 mt-1 transform -rotate-45 group-hover:translate-x-1 transition"></i>
                        <span>Kalender Akademik</span>
                    </a>
                    <a href="/akademik/wisuda" class="flex items-start gap-3 hover:text-blue-500 transition group">
                        <i class="fa-solid fa-location-arrow text-blue-500 mt-1 transform -rotate-45 group-hover:translate-x-1 transition"></i>
                        <span>Pendaftaran Wisuda Online</span>
                    </a>
                </div>

            @elseif(request()->is('pegawai*'))
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-y-6 gap-x-6 text-xs md:text-sm text-gray-700 font-medium">
                    <a href="/pegawai/presensi" class="flex items-start gap-3 hover:text-blue-500 transition group">
                        <i class="fa-solid fa-location-arrow text-blue-500 mt-1 transform -rotate-45 group-hover:translate-x-1 transition"></i>
                        <span>Presensi & Kehadiran</span>
                    </a>
                    <a href="/pegawai/cuti" class="flex items-start gap-3 hover:text-blue-500 transition group">
                        <i class="fa-solid fa-location-arrow text-blue-500 mt-1 transform -rotate-45 group-hover:translate-x-1 transition"></i>
                        <span>Pengajuan Cuti & Izin</span>
                    </a>
                    <a href="/pegawai/profil-dosen" class="flex items-start gap-3 hover:text-blue-500 transition group">
                        <i class="fa-solid fa-location-arrow text-blue-500 mt-1 transform -rotate-45 group-hover:translate-x-1 transition"></i>
                        <span>Profil Dosen</span>
                    </a>
                    <a href="/pegawai/direktori" class="flex items-start gap-3 hover:text-blue-500 transition group">
                        <i class="fa-solid fa-location-arrow text-blue-500 mt-1 transform -rotate-45 group-hover:translate-x-1 transition"></i>
                        <span>Direktori Staf & Dosen</span>
                    </a>
                </div>

            @elseif(request()->is('aset*'))
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-y-6 gap-x-6 text-xs md:text-sm text-gray-700 font-medium">
                    <a href="/aset/inventaris" class="flex items-start gap-3 hover:text-blue-500 transition group">
                        <i class="fa-solid fa-location-arrow text-blue-500 mt-1 transform -rotate-45 group-hover:translate-x-1 transition"></i>
                        <span>Inventaris Barang Kampus</span>
                    </a>
                    <a href="/aset/peminjaman" class="flex items-start gap-3 hover:text-blue-500 transition group">
                        <i class="fa-solid fa-location-arrow text-blue-500 mt-1 transform -rotate-45 group-hover:translate-x-1 transition"></i>
                        <span>Peminjaman Ruang & Alat</span>
                    </a>
                    <a href="/aset/pemeliharaan" class="flex items-start gap-3 hover:text-blue-500 transition group">
                        <i class="fa-solid fa-location-arrow text-blue-500 mt-1 transform -rotate-45 group-hover:translate-x-1 transition"></i>
                        <span>Laporan Kerusakan / Maintenance</span>
                    </a>
                    <a href="/aset/bmn" class="flex items-start gap-3 hover:text-blue-500 transition group">
                        <i class="fa-solid fa-location-arrow text-blue-500 mt-1 transform -rotate-45 group-hover:translate-x-1 transition"></i>
                        <span>Barang Milik Negara (BMN)</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</section>
<footer class="bg-blue-500 text-white">
    <div class="max-w-7xl mx-auto px-6 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
            <div>
                <a href="/" class="flex items-center gap-4">
                    <img src="{{ asset('images/ic_logo.svg') }}" alt="Logo Untirta" class="w-16 h-16 object-contain">
                    <div class="leading-none">
                        <h1 class="text-4xl font-bold">Dashboard</h1>
                        <p class="text-3xl font-light mt-1">Untirta</p>
                    </div>
                </a>
                <div class="mt-8 space-y-3 text-sm md:text-base">
                    <p>Jl. Raya Palka Km 3 Sindangsari, Pabuaran, Kabupaten Serang, Provinsi Banten</p>
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-phone"></i>
                        <span>+62 254 3204321</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-fax"></i>
                        <span>+62 254 281254</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-envelope"></i>
                        <span>humas@untirta.ac.id</span>
                    </div>
                </div>
            </div>
            <div class="lg:justify-self-end">
                <h2 class="font-semibold uppercase tracking-wider mb-5">Main Menu</h2>
                <ul class="space-y-3">
                    <li><a href="/" class="hover:font-bold transition">Beranda</a></li>
                    <li><a href="/akademik" class="hover:font-bold transition">Akademik</a></li>
                    <li><a href="/aset" class="hover:font-bold transition">Aset</a></li>
                    <li><a href="/pegawai" class="hover:font-bold transition">Pegawai</a></li>
                    <li><a href="/infrastruktur" class="hover:font-bold transition">Infrastruktur</a></li>
                </ul>
            </div>
        </div>
        <hr class="my-8 border-white/20">
        <p class="text-sm text-center lg:text-left text-white/80">
            © {{ date('Y') }} Universitas Sultan Ageng Tirtayasa. All Rights Reserved.
        </p>
    </div>
</footer>
