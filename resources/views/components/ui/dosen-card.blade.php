<div class="bg-slate-50 p-4 md:p-8 flex justify-center items-center font-sans">
    <div
        class="relative w-full max-w-4xl bg-white rounded-2xl border border-slate-100 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07)] p-6 md:p-8 overflow-hidden">

        <div class="absolute -right-6 -top-6 text-slate-100/80 w-48 h-48 pointer-events-none z-0">
            <svg fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 3L1 9L12 15L21 10.09V17H23V9M5 13.18V17.18L12 21L19 17.18V13.18L12 17L5 13.18Z"/>
            </svg>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row items-start gap-6">

            <div class="relative shrink-0 mx-auto md:mx-0">
                <img
                    src="https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=256&q=80"
                    alt="Foto Profil"
                    class="w-28 h-28 md:w-32 md:h-32 rounded-2xl object-cover shadow-md border-2 border-white">
                <div
                    class="absolute -bottom-2 -right-2 bg-teal-700 text-white p-1.5 rounded-xl shadow-md border-2 border-white"
                    title="Terverifikasi">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>

            <div class="flex-1 w-full text-center md:text-left">
                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">
                    {{ $profile['nama'] ?? 'Anis Fuad, S.Sos., M.Si.' }}
                </h1>

                <div class="mt-3 space-y-2 text-sm md:text-base text-slate-600">
                    <div class="flex items-center justify-center md:justify-start gap-2.5">
                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                        </svg>
                        <span>NIP: <strong
                                class="font-semibold text-slate-700">{{ $profile['nip'] ?? '198009082006041002' }}</strong></span>
                    </div>

                    <div class="flex items-center justify-center md:justify-start gap-2.5">
                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <span>{{ $profile['prodi'] ?? 'Program Studi Ilmu Pemerintahan' }}</span>
                    </div>

                    <div class="flex items-center justify-center md:justify-start gap-2.5">
                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>{{ $profile['fakultas'] ?? 'FISIP - Universitas Sultan Ageng Tirtayasa' }}</span>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-center md:justify-start gap-2">
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200/60 shadow-sm">
                        Total Publikasi: {{ $profile['total_publikasi'] ?? '31' }}
                    </span>
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200/60 shadow-sm">
                        SISTER Terverifikasi
                    </span>
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200/60 shadow-sm">
                        Aktif Meneliti
                    </span>
                </div>
            </div>

            <div class="flex flex-row md:flex-col gap-3 w-full md:w-auto shrink-0 pt-2 md:pt-0">
                <a href="#"
                   class="flex-1 md:flex-initial inline-flex justify-center items-center px-5 py-2.5 bg-teal-700 hover:bg-teal-800 text-white text-sm font-medium rounded-xl shadow-sm hover:shadow transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                    Edit Profil
                </a>
                <a href="#"
                   class="flex-1 md:flex-initial inline-flex justify-center items-center px-5 py-2.5 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-xl border border-slate-300 shadow-sm hover:shadow-xs transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">
                    Unduh CV
                </a>
            </div>
        </div>
    </div>
</div>
