@php
    $navLinks = [
        [
            'name' => 'Dashboard',
            'url' => '/',
            'active' => request()->is('/'),
            'icon' => 'fa-solid fa-gauge-high',
        ],
        [
            'name' => 'Akademik',
            'url' => '/akademik',
            'active' => request()->is('akademik*'),
            'icon' => 'fa-solid fa-graduation-cap',
        ],
        [
            'name' => 'Aset',
            'url' => '/aset',
            'active' => request()->is('aset*'),
            'icon' => 'fa-solid fa-boxes-stacked',
        ],
        [
            'name' => 'Pegawai',
            'url' => '/pegawai',
            'active' => request()->is('pegawai*'),
            'icon' => 'fa-solid fa-users',
        ],
    ];
@endphp

<!-- Mobile Backdrop -->
<div x-show="mobileSidebarOpen"
    x-transition:enter="transition-opacity ease-linear duration-250"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-250"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="closeMobileSidebar()"
    class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs lg:hidden"
    aria-hidden="true"
    style="display: none;">
</div>

<!-- Mobile Off-Canvas Drawer -->
<div x-show="mobileSidebarOpen"
    x-transition:enter="transition ease-in-out duration-300 transform"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in-out duration-300 transform"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-slate-900 border-r border-slate-800 text-white lg:hidden shadow-2xl"
    role="dialog"
    aria-modal="true"
    aria-label="Navigasi Utama Mobile"
    style="display: none;">

    <!-- Mobile Drawer Header -->
    <div class="flex h-16 shrink-0 items-center justify-between px-5 border-b border-slate-800">
        <a href="/" class="flex items-center gap-3 focus:outline-hidden focus:ring-2 focus:ring-cyan-400 rounded-md">
            <img src="{{ asset('images/untirta_logo.png') }}" alt="Logo Untirta" class="size-8 object-contain">
            <div class="leading-tight">
                <span class="block text-sm font-bold tracking-wider text-white">SATUDATA</span>
                <span class="block text-[11px] font-semibold text-cyan-400">UNTIRTA</span>
            </div>
        </a>
        <button type="button"
            @click="closeMobileSidebar()"
            class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white focus:outline-hidden focus:ring-2 focus:ring-cyan-400"
            aria-label="Tutup navigasi">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
    </div>

    <!-- Mobile Nav Items -->
    <div class="flex-1 overflow-y-auto px-4 py-4 space-y-1.5">
        <div class="px-2 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Menu Utama</div>
        @foreach ($navLinks as $item)
            <a href="{{ $item['url'] }}"
                @if ($item['active']) aria-current="page" @endif
                class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl text-sm font-medium transition-colors {{ $item['active'] ? 'bg-cyan-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="{{ $item['icon'] }} w-5 text-center text-base {{ $item['active'] ? 'text-white' : 'text-slate-400' }}"></i>
                <span>{{ $item['name'] }}</span>
            </a>
        @endforeach
    </div>

    <!-- Mobile Bottom Profile & TirtaAgent -->
    <div class="p-4 border-t border-slate-800 bg-slate-900/95 shrink-0 space-y-3">
        <button type="button"
            class="w-full flex items-center justify-center gap-2.5 px-3.5 py-2.5 rounded-xl bg-cyan-950/60 border border-cyan-700/50 text-cyan-300 hover:bg-cyan-900/80 hover:text-white transition focus:outline-hidden focus:ring-2 focus:ring-cyan-400 text-sm font-medium"
            data-tirta-chat-open
            @click="closeMobileSidebar()"
            aria-label="Buka TirtaAgent">
            <i class="fa-solid fa-robot text-base text-cyan-400"></i>
            <span>Tanya TirtaAgent</span>
        </button>

        <div x-data="{ mobileProfileOpen: false }" class="relative pt-2">
            <button type="button"
                @click="mobileProfileOpen = !mobileProfileOpen"
                class="flex items-center justify-between w-full p-2 rounded-xl hover:bg-slate-800 transition text-left focus:outline-hidden focus:ring-2 focus:ring-cyan-400">
                <div class="flex items-center gap-3 min-w-0">
                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                        alt="Avatar Pengembang"
                        class="size-9 rounded-full ring-1 ring-white/20 shrink-0">
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-white truncate">Pengembang</div>
                        <div class="text-xs text-slate-400 truncate">pengembang@satudata.untirta.ac.id</div>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-up text-xs text-slate-400 transition-transform duration-200"
                    :class="mobileProfileOpen ? '' : 'rotate-180'"></i>
            </button>

            <!-- Mobile Profile Menu -->
            <div x-show="mobileProfileOpen"
                x-transition
                class="mt-2 p-1.5 rounded-xl bg-slate-800/90 border border-slate-700/60 shadow-lg"
                style="display: none;">
                <button type="button"
                    class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-xs font-medium text-rose-400 hover:bg-rose-950/40 hover:text-rose-300 transition">
                    <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                    <span>Keluar</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Desktop Collapsible Sidebar -->
<aside
    class="satudata-sidebar satudata-sidebar-desktop select-none"
    aria-label="Navigasi Utama Desktop">

    <!-- Desktop Logo / Header -->
    <div class="h-16 shrink-0 flex items-center border-b border-slate-800 px-3">
        <a href="/" class="flex items-center w-full overflow-hidden focus:outline-hidden focus:ring-2 focus:ring-cyan-400 rounded-lg p-1">
            <div class="w-14 h-10 flex items-center justify-center shrink-0">
                <img src="{{ asset('images/untirta_logo.png') }}"
                    alt="Logo Untirta"
                    class="size-8 object-contain">
            </div>
            <div class="leading-tight whitespace-nowrap satudata-sidebar-label">
                <span class="block text-sm font-bold tracking-wider text-white">SATUDATA</span>
                <span class="block text-[11px] font-semibold text-cyan-400">UNTIRTA</span>
            </div>
        </a>
    </div>

    <!-- Desktop Nav Items -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1.5" aria-label="Menu Aplikasi">
        <div class="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 satudata-sidebar-label">
            Menu Utama
        </div>
        @foreach ($navLinks as $item)
            <a href="{{ $item['url'] }}"
                @if ($item['active']) aria-current="page" @endif
                class="group relative flex items-center h-11 rounded-xl text-sm font-medium transition-colors duration-150 {{ $item['active'] ? 'bg-cyan-600 text-white shadow-md shadow-cyan-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                <span class="w-14 h-11 flex items-center justify-center shrink-0">
                    <i class="{{ $item['icon'] }} text-base {{ $item['active'] ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                </span>
                <span class="truncate whitespace-nowrap satudata-sidebar-label">
                    {{ $item['name'] }}
                </span>

                <!-- Floating tooltip on hover when collapsed -->
                <div class="satudata-sidebar-tooltip pointer-events-none absolute left-full ml-3 hidden items-center rounded-md bg-slate-800 px-2.5 py-1 text-xs font-semibold text-white shadow-xl border border-slate-700 whitespace-nowrap z-50"
                    role="tooltip">
                    {{ $item['name'] }}
                </div>
            </a>
        @endforeach
    </nav>

    <!-- Desktop Bottom Section: Profile with Dropdown Menu -->
    <div x-data="{ profileMenuOpen: false }"
        @click.outside="profileMenuOpen = false"
        @keydown.escape.window="profileMenuOpen = false"
        class="relative p-3 border-t border-slate-800 bg-slate-900/95 shrink-0">

        <!-- Dropdown Popover Menu -->
        <div x-show="profileMenuOpen"
            x-transition:enter="transition ease-out duration-150 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="satudata-profile-popover absolute z-50 rounded-2xl bg-slate-800 border border-slate-700/80 p-1.5 shadow-2xl shadow-slate-950/80"
            style="display: none;">
            
            <div class="px-3 py-2 border-b border-slate-700/60">
                <div class="text-xs font-semibold text-white truncate">Pengembang</div>
                <div class="text-[11px] text-slate-400 truncate">pengembang@untirta.ac.id</div>
                <span class="inline-flex items-center mt-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-cyan-950 text-cyan-400 border border-cyan-800/50">
                    Administrator
                </span>
            </div>

            <div class="pt-1">
                <button type="button"
                    @click="profileMenuOpen = false"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-rose-400 hover:bg-rose-950/40 hover:text-rose-300 transition focus:outline-hidden focus:ring-2 focus:ring-rose-500">
                    <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                    <span>Keluar</span>
                </button>
            </div>
        </div>

        <!-- User Profile Trigger Button -->
        <button type="button"
            @click="profileMenuOpen = !profileMenuOpen"
            class="group relative flex items-center h-12 w-full rounded-xl text-left hover:bg-slate-800/60 transition focus:outline-hidden focus:ring-2 focus:ring-cyan-400"
            :class="profileMenuOpen ? 'bg-slate-800/80 ring-1 ring-slate-700' : ''"
            aria-haspopup="true"
            :aria-expanded="profileMenuOpen"
            title="Menu Pengguna"
            aria-label="Menu Pengguna">
            <span class="w-14 h-12 flex items-center justify-center shrink-0">
                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                    alt="Avatar Pengembang"
                    class="size-8 rounded-full ring-1 ring-white/20 group-hover:ring-cyan-400 transition-all shrink-0">
            </span>
            <div class="min-w-0 flex-1 satudata-sidebar-label">
                <div class="text-xs font-semibold text-white truncate">Pengembang</div>
                <div class="text-[11px] text-slate-400 truncate">pengembang@untirta.ac.id</div>
            </div>
            <span class="pr-2 text-slate-400 satudata-sidebar-label">
                <i class="fa-solid fa-ellipsis-vertical text-xs"></i>
            </span>

            <!-- Floating tooltip on hover when collapsed and menu is closed -->
            <div x-show="!profileMenuOpen"
                class="satudata-sidebar-tooltip pointer-events-none absolute left-full ml-3 hidden items-center rounded-md bg-slate-800 px-2.5 py-1 text-xs font-semibold text-white shadow-xl border border-slate-700 whitespace-nowrap z-50"
                role="tooltip">
                Profil & Keluar
            </div>
        </button>
    </div>
</aside>
