@props([
    'endpoint' => route('tirta-agent.chat'),
])

<div class="fixed inset-0 z-50 hidden" data-tirta-chat-shell aria-hidden="true">
    <button type="button" class="absolute inset-0 bg-slate-950/45 backdrop-blur-[2px]" data-tirta-chat-close
            aria-label="Tutup TirtaAgent"></button>

    <aside
        class="absolute right-0 top-0 flex h-full w-full max-w-[480px] translate-x-full flex-col border-l border-slate-200 bg-white shadow-2xl transition-transform duration-200 sm:right-4 sm:top-4 sm:h-[calc(100%-2rem)] sm:rounded-2xl sm:border sm:shadow-[0_24px_70px_rgba(15,23,42,0.22)]"
        data-tirta-chat-panel>
        <div class="border-b border-slate-200 bg-slate-950 px-5 py-4 text-white sm:rounded-t-2xl">
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-cyan-400 text-slate-950">
                        <i class="fa-solid fa-comments text-lg"></i>
                        <span
                            class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full border-2 border-slate-950 bg-emerald-400"></span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h2 class="truncate text-base font-extrabold">TirtaAgent</h2>
                            <span
                                class="rounded-full bg-white/10 px-2 py-0.5 text-[11px] font-bold text-cyan-100 ring-1 ring-white/10">Beta</span>
                        </div>
                        <p class="text-xs font-semibold text-cyan-100">Asisten SatuData Untirta</p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-1">
                    <button type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-xl text-slate-300 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-cyan-300"
                            data-tirta-chat-reset aria-label="Mulai percakapan baru" title="Percakapan baru">
                        <i class="fa-solid fa-rotate-right text-sm"></i>
                    </button>
                    <button type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-xl text-slate-300 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-cyan-300"
                            data-tirta-chat-close aria-label="Tutup TirtaAgent">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex min-h-0 flex-1 flex-col" data-tirta-chat data-endpoint="{{ $endpoint }}"
             data-csrf="{{ csrf_token() }}">
            <input type="hidden" data-tirta-conversation-id value="">
            <div class="border-b border-slate-200 bg-white px-5 py-4">
                <p class="text-sm font-semibold leading-6 text-slate-700">
                    Tanya arah data, minta ringkasan, atau lanjutkan pertanyaan sebelumnya.
                </p>
                <div class="mt-3 flex gap-2 overflow-x-auto pb-1 tirta-quick-prompts" aria-label="Contoh pertanyaan">
                    <button type="button"
                            class="shrink-0 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-cyan-200 hover:bg-cyan-50 hover:text-cyan-700"
                            data-tirta-prompt="Tampilkan ringkasan data akademik Untirta.">
                        Ringkasan akademik
                    </button>
                    <button type="button"
                            class="shrink-0 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-cyan-200 hover:bg-cyan-50 hover:text-cyan-700"
                            data-tirta-prompt="Berapa jumlah mahasiswa lulus per fakultas?">
                        Lulusan per fakultas
                    </button>
                    <button type="button"
                            class="shrink-0 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-cyan-200 hover:bg-cyan-50 hover:text-cyan-700"
                            data-tirta-prompt="Data apa saja yang tersedia di SATUDATA Untirta?">
                        Data tersedia
                    </button>
                </div>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-slate-50 p-4 sm:p-5" data-tirta-messages>
                <div class="flex items-start gap-3" data-tirta-welcome>
                    <div
                        class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-cyan-700">
                        <i class="fa-solid fa-robot text-sm"></i>
                    </div>
                    <div
                        class="max-w-[86%] rounded-2xl rounded-tl-md bg-white p-4 text-sm leading-6 text-slate-700 shadow-sm ring-1 ring-slate-200">
                        <p class="font-semibold text-slate-900">Halo, saya TirtaAgent.</p>
                        <p class="mt-1">Saya bisa bantu membaca data akademik, aset, pegawai, dan navigasi SATUDATA.
                        </p>
                    </div>
                </div>

                <div class="hidden items-start gap-3" data-tirta-typing>
                    <div
                        class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-cyan-700">
                        <i class="fa-solid fa-robot text-sm"></i>
                    </div>
                    <div class="rounded-2xl rounded-tl-md bg-white px-4 py-3 shadow-sm ring-1 ring-slate-200">
                        <span class="tirta-typing-dots" aria-label="TirtaAgent sedang mengetik">
                            <span></span><span></span><span></span>
                        </span>
                    </div>
                </div>
            </div>

            <form class="border-t border-slate-200 bg-white p-4 sm:rounded-b-2xl" data-tirta-form>
                <div class="flex items-end gap-3">
                    <textarea
                        class="max-h-32 min-h-12 flex-1 resize-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-5 text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100 disabled:cursor-not-allowed disabled:bg-slate-100"
                        name="message" rows="1" maxlength="1000"
                        placeholder="Tanya tentang data akademik, aset, pegawai..." required
                        data-tirta-input></textarea>
                    <button
                        type="submit"
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-cyan-600 text-white shadow-sm transition hover:bg-cyan-700 focus:outline-none focus:ring-4 focus:ring-cyan-100 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none"
                        data-tirta-submit
                        aria-label="Kirim pesan">
                        <span data-send-icon>
                            <i class="fa-solid fa-paper-plane"></i>
                        </span>
                        <span class="hidden" data-send-spinner>
                            <i class="fa-solid fa-spinner fa-spin"></i>
                        </span>
                    </button>
                </div>
                <div class="mt-2 flex items-center justify-between gap-3 text-xs text-slate-500">
                    <p data-tirta-status></p>
                    <div class="flex items-center gap-3">
                        <span data-tirta-counter>0/1000</span>
                        <p class="shrink-0">Enter untuk kirim</p>
                    </div>
                </div>
            </form>
        </div>
    </aside>
</div>
