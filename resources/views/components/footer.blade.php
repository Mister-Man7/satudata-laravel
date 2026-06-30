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
