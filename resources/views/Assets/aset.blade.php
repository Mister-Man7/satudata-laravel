<x-layout>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>

    {{-- Breadcrumb Navigation (For Gedung and Ruangan levels) --}}
    @if(($level ?? 'kampus') !== 'kampus')
        <nav class="mb-5 flex items-center gap-2 text-xs sm:text-sm font-medium text-gray-500" aria-label="Breadcrumb">
            <a href="{{ route('aset.index') }}" class="hover:text-blue-600 transition-colors flex items-center gap-1.5">
                <i class="fa-solid fa-boxes-stacked text-xs"></i>
                <span>Aset</span>
            </a>
            <i class="fa-solid fa-chevron-right text-[10px] text-gray-400"></i>
            @if(($level ?? '') === 'gedung')
                <span class="text-gray-900 font-semibold truncate">{{ $title }}</span>
            @elseif(($level ?? '') === 'ruangan')
                <a href="javascript:history.back()" class="hover:text-blue-600 transition-colors">
                    Daftar Gedung
                </a>
                <i class="fa-solid fa-chevron-right text-[10px] text-gray-400"></i>
                <span class="text-gray-900 font-semibold truncate">{{ $title }}</span>
            @endif
        </nav>
    @endif
    
    {{-- Warning Banner if API fails --}}
    @if(!empty($warning))
        <div class="mb-6 px-4 py-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-center gap-3 shadow-xs">
            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-triangle-exclamation text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-sm">{{ $warning }}</p>
            </div>
            <a href="{{ url()->current() }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-amber-200 hover:bg-amber-300 text-amber-900 transition-colors shrink-0">
                Muat Ulang
            </a>
        </div>
    @endif

    {{-- Penyegaran data aset: satu tombol saja, tanpa isian apa pun dari pengguna.
         Penarikan dijalankan host aplikasi memakai Chromium lokal (satu-satunya klien
         yang lolos Cloudflare dari server); kredensial SIMANTAP tetap di .env server. --}}
    @if(($level ?? 'kampus') === 'kampus')
        <div id="sync-aset"
             class="mb-6 rounded-2xl border border-slate-100 bg-slate-50/70 px-4 py-3 flex flex-wrap items-center gap-3"
             data-url="{{ route('aset.sync-data') }}"
             data-status-url="{{ route('aset.sync-status') }}"
             data-csrf="{{ csrf_token() }}">
            <div class="flex items-center gap-2 text-sm text-slate-600">
                <i class="fa-solid fa-database text-slate-400"></i>
                <span>Data aset dan total unit dibaca dari database SATUDATA hasil sinkronisasi SIMANTAP.</span>
            </div>
            <div class="flex-1"></div>
            <span id="sync-aset-status" class="text-xs text-slate-500"></span>
            <button type="button" id="sync-aset-button"
                    class="inline-flex items-center gap-2 rounded-lg bg-cyan-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-cyan-700 transition-colors disabled:opacity-50 disabled:pointer-events-none">
                <i class="fa-solid fa-rotate text-[11px]"></i>
                <span>Tarik data aset</span>
            </button>
        </div>
    @endif

    {{-- Campus Executive KPI & Breakdown Chart --}}
    @if(($level ?? 'kampus') === 'kampus' && !empty($summaryStats))
        <x-ui.aset-summary-card :stats="$summaryStats" />
    @endif

    {{-- Campus / Gedung / Ruangan Card Grid --}}
    <x-ui.aset-card :datas="$datas" :level="$level ?? 'kampus'"></x-ui.aset-card>

    @if(($level ?? 'kampus') === 'kampus')
        <script>
            // Tombol "Tarik data aset": meminta server menyegarkan data BMN dari SIMANTAP
            // (Chromium lokal di host aplikasi) lalu memantau statusnya. Operator tidak
            // mengisi kredensial atau token apa pun.
            (function () {
                const panel = document.getElementById('sync-aset');
                if (!panel) return;

                const url = panel.dataset.url;
                const statusUrl = panel.dataset.statusUrl;
                const csrf = panel.dataset.csrf;
                const tombol = document.getElementById('sync-aset-button');
                const status = document.getElementById('sync-aset-status');

                const writeStatus = (teks, kelas) => { status.textContent = teks || ''; status.className = 'text-xs ' + (kelas || 'text-slate-500'); };
                const lockButton = (mati) => { tombol.disabled = mati; tombol.classList.toggle('opacity-60', mati); };

                async function checkStatus() {
                    const respons = await fetch(statusUrl, { headers: { accept: 'application/json' } });
                    return respons.ok ? respons.json() : { status: 'belum', message: '' };
                }

                async function pantau(batasDetik) {
                    const mulai = Date.now();

                    while (Date.now() - mulai < batasDetik * 1000) {
                        await new Promise((selesai) => setTimeout(selesai, 3000));

                        let hasil;
                        try { hasil = await checkStatus(); } catch (e) { hasil = { status: 'belum', message: '' }; }

                        if (hasil.status === 'selesai') {
                            if (hasil.has_data === false) {
                                writeStatus(hasil.message || 'Tidak ada data aset baru pada sesi ini.', 'text-slate-500');
                                lockButton(false);
                                return;
                            }

                            writeStatus(hasil.message || 'Data aset berhasil disegarkan. Memuat ulang...', 'text-emerald-600');
                            window.location.reload();
                            return;
                        }

                        if (hasil.status === 'gagal') {
                            writeStatus(hasil.message || 'Penyegaran data aset gagal.', 'text-rose-600');
                            lockButton(false);
                            return;
                        }

                        writeStatus('Sedang menyegarkan data aset dari SIMANTAP...', 'text-slate-500');
                    }

                    writeStatus('Masih berjalan. Muat ulang halaman sebentar lagi untuk melihat hasilnya.', 'text-amber-600');
                    lockButton(false);
                }

                tombol.addEventListener('click', async function () {
                    lockButton(true);
                    writeStatus('Meminta penyegaran data aset...', 'text-slate-500');

                    try {
                        const respons = await fetch(url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify({}),
                        });

                        const hasil = await respons.json().catch(() => ({}));

                        if (hasil.status === 'diam') {
                            writeStatus('Data aset baru saja disegarkan.', 'text-slate-500');
                            lockButton(false);
                            return;
                        }

                        if (!respons.ok && respons.status !== 202) {
                            writeStatus(hasil.message || ('Permintaan penyegaran gagal (HTTP ' + respons.status + ').'), 'text-rose-600');
                            lockButton(false);
                            return;
                        }

                        writeStatus('Penyegaran dimulai...', 'text-slate-500');
                        await pantau(240);
                    } catch (e) {
                        writeStatus('Gagal menghubungi server: ' + e.message, 'text-rose-600');
                        lockButton(false);
                    }
                });
            })();
        </script>
    @endif
</x-layout>
