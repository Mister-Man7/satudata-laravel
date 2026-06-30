@props([
    'endpoint' => route('tirta-agent.chat'),
])

<div
    class="fixed inset-0 z-50 hidden"
    data-tirta-chat-shell
    aria-hidden="true"
>
    <button
        type="button"
        class="absolute inset-0 bg-slate-950/35"
        data-tirta-chat-close
        aria-label="Tutup TirtaAgent"
    ></button>

    <aside
        class="absolute right-0 top-0 flex h-full w-full max-w-[440px] translate-x-full flex-col border-l border-slate-200 bg-white shadow-2xl transition-transform duration-200"
        data-tirta-chat-panel
    >
        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-950 px-5 py-4 text-white">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-400 text-slate-950">
                    <i class="fa-solid fa-comments text-lg"></i>
                </div>
                <div>
                    <h2 class="text-base font-extrabold">TirtaAgent</h2>
                    <p class="text-xs font-semibold text-cyan-100">Asisten SatuData Untirta</p>
                </div>
            </div>

            <button
                type="button"
                class="flex h-10 w-10 items-center justify-center rounded-xl text-slate-300 transition hover:bg-white/10 hover:text-white"
                data-tirta-chat-close
                aria-label="Tutup TirtaAgent"
            >
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div
            class="flex min-h-0 flex-1 flex-col"
            data-tirta-chat
            data-endpoint="{{ $endpoint }}"
            data-csrf="{{ csrf_token() }}"
        >
            <div class="border-b border-slate-200 bg-white px-5 py-3 text-sm leading-6 text-slate-600">
                Tanya arah data, minta penjelasan halaman, atau lanjutkan pertanyaan sebelumnya.
            </div>

            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-slate-50 p-4 sm:p-5" data-tirta-messages>
                <div class="max-w-[85%] rounded-2xl rounded-tl-md bg-white p-4 text-sm leading-6 text-slate-700 shadow-sm ring-1 ring-slate-200">
                    Halo, saya TirtaAgent. Mau cari data apa hari ini?
                </div>
            </div>

            <form class="border-t border-slate-200 bg-white p-4" data-tirta-form>
                <div class="flex gap-3">
                    <textarea
                        class="min-h-12 flex-1 resize-none rounded-xl border border-slate-200 px-4 py-3 text-sm leading-5 text-slate-800 outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
                        name="message"
                        rows="1"
                        maxlength="1000"
                        placeholder="Tanya tentang data akademik, aset, pegawai..."
                        required
                        data-tirta-input
                    ></textarea>
                    <button
                        type="submit"
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-cyan-600 text-white transition hover:bg-cyan-700 focus:outline-none focus:ring-4 focus:ring-cyan-100 disabled:cursor-not-allowed disabled:bg-slate-300"
                        data-tirta-submit
                        aria-label="Kirim pesan"
                    >
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
                <p class="mt-2 text-xs text-slate-500" data-tirta-status></p>
            </form>
        </div>
    </aside>
</div>
