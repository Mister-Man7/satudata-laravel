@props(['title' => null])

<header class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white/95 backdrop-blur-md px-4 sm:px-6 lg:px-8 shadow-xs">
    <!-- Left: Toggles & Breadcrumb -->
    <div class="flex items-center gap-3">
        <!-- Mobile Drawer Button (Mobile Only) -->
        <button type="button"
            @click="toggleMobileSidebar()"
            class="flex lg:hidden -ml-1.5 h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-hidden focus:ring-2 focus:ring-cyan-500"
            aria-label="Buka navigasi mobile">
            <i class="fa-solid fa-bars text-lg"></i>
        </button>

        <!-- Desktop Collapse Button (Desktop Only) -->
        <button type="button"
            @click="toggleSidebar()"
            class="hidden lg:flex -ml-1.5 h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition focus:outline-hidden focus:ring-2 focus:ring-cyan-500"
            aria-label="Perkecil atau perluas navigasi samping"
            title="Perkecil/Perluas Menu">
            <i class="fa-solid fa-bars-staggered text-base"></i>
        </button>

        <!-- Context Breadcrumb -->
        <div class="flex items-center gap-2 text-sm text-slate-500">
            <span class="font-medium text-slate-800 hidden sm:inline">SATUDATA UNTIRTA</span>
            @if ($title)
                <span class="text-slate-300 hidden sm:inline">/</span>
                <span class="font-semibold text-cyan-700 truncate max-w-[200px] sm:max-w-xs">{{ $title }}</span>
            @endif
        </div>
    </div>

    <!-- Right: TirtaAgent & Profile -->
    <div class="flex items-center gap-3">
        <!-- TirtaAgent Trigger -->
        <button type="button"
            class="flex items-center gap-2 rounded-full border border-cyan-200 bg-cyan-50/90 px-3.5 py-1.5 text-xs font-semibold text-cyan-800 transition hover:bg-cyan-100 hover:border-cyan-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-500"
            data-tirta-chat-open
            aria-label="Buka asisten virtual TirtaAgent"
            title="TirtaAgent">
            <i class="fa-solid fa-robot text-sm text-cyan-600"></i>
            <span class="hidden md:inline">TirtaAgent</span>
        </button>
    </div>
</header>
