<nav class="bg-gray-800">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="flex h-16 items-center justify-between">
        <div class="flex items-center">
        <div class="shrink-0">
            <img src="{{ asset('images/untirta_logo.png') }}" alt="Logo Universitas Sultan Ageng Tirtayasa" class="size-8" />
        </div>
        <div class="hidden md:block">
            <div class="ml-10 flex items-baseline space-x-4">
            <x-nav-link href="/" :active="request()->is('/')">Dashboard</x-nav-link>
            <x-nav-link href="/akademik" :active="request()->is('akademik')">Akademik</x-nav-link>
            <x-nav-link href="/aset" :active="request()->is('aset')">Aset</x-nav-link>
            <x-nav-link href="/pegawai" :active="request()->is('pegawai')">Pegawai</x-nav-link>
            <x-nav-link href="/infrastruktur" :active="request()->is('infrastruktur')">Infrastruktur</x-nav-link>
            </div>
        </div>
        </div>
        <div class="hidden md:block">
        <div class="ml-4 flex items-center md:ml-6">
            <!-- Profile dropdown -->
            <el-dropdown class="relative ml-3">
            <button class="relative flex max-w-xs items-center rounded-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">
                <span class="absolute -inset-1.5"></span>
                <span class="sr-only">Open user menu</span>
                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="" class="size-8 rounded-full outline -outline-offset-1 outline-white/10" />
            </button>

            <el-menu anchor="bottom end" popover class="w-48 origin-top-right rounded-md bg-white py-1 shadow-lg outline-1 outline-black/5 transition transition-discrete [--anchor-gap:--spacing(2)] data-closed:scale-95 data-closed:transform data-closed:opacity-0 data-enter:duration-100 data-enter:ease-out data-leave:duration-75 data-leave:ease-in">
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 focus:bg-gray-100 focus:outline-hidden">Your profile</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 focus:bg-gray-100 focus:outline-hidden">Settings</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 focus:bg-gray-100 focus:outline-hidden">Sign out</a>
            </el-menu>
            </el-dropdown>
        </div>
        </div>
        <div class="-mr-2 flex md:hidden">
        <!-- Mobile menu button -->
        <button type="button" data-mobile-menu-button aria-controls="mobile-menu" aria-expanded="false" class="relative inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-white/5 hover:text-white focus:outline-2 focus:outline-offset-2 focus:outline-indigo-500">
            <span class="absolute -inset-0.5"></span>
            <span class="sr-only">Open main menu</span>
            <svg data-menu-open-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6">
            <path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <svg data-menu-close-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="hidden size-6">
            <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>
        </div>
    </div>
    </div>

    <div id="mobile-menu" data-mobile-menu class="hidden md:hidden">
    <div class="space-y-1 px-2 pt-2 pb-3 sm:px-3">
        <!-- Current: "bg-gray-900 text-white", Default: "text-gray-300 hover:bg-white/5 hover:text-white" -->
        <x-nav-link href="/" :active="request()->is('/')" mobile>Dashboard</x-nav-link>
        <x-nav-link href="/akademik" :active="request()->is('akademik')" mobile>Akademik</x-nav-link>
        <x-nav-link href="/aset" :active="request()->is('aset')" mobile>Aset</x-nav-link>
        <x-nav-link href="/pegawai" :active="request()->is('pegawai')" mobile>Pegawai</x-nav-link>
        <x-nav-link href="/infrastruktur" :active="request()->is('infrastruktur')" mobile>Infrastruktur</x-nav-link>
    </div>
    <div class="border-t border-white/10 pt-4 pb-3">
        <div class="flex items-center px-5">
        <div class="shrink-0">
            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="" class="size-10 rounded-full outline -outline-offset-1 outline-white/10" />
        </div>
        <div class="ml-3">
            <div class="text-base/5 font-medium text-white">Tom Cook</div>
            <div class="text-sm font-medium text-gray-400">tom@example.com</div>
        </div>
        </div>
        <div class="mt-3 space-y-1 px-2">
        <a href="#" class="block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-white/5 hover:text-white">Your profile</a>
        <a href="#" class="block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-white/5 hover:text-white">Settings</a>
        <a href="#" class="block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-white/5 hover:text-white">Sign out</a>
        </div>
    </div>
    </div>
</nav>
