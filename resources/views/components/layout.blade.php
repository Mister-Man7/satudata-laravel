<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <title>
        {{ $title ?? ucwords(str_replace('-', ' ', request()->segment(count(request()->segments())) ?? 'dashboard')) }}
        | SATUDATA UNTIRTA</title>

    <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    {{-- Livewire dimuat eksplisit (sekaligus membawa Alpine dan direktifnya) supaya tidak
         bergantung pada penyuntikan otomatis dan urutannya pasti sebelum app.js. --}}
    @livewireStyles
    @livewireScripts
    <script>
        if (localStorage.getItem('sidebar_collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>
</head>

<body class="h-full bg-gray-100 antialiased">

    <div x-data="{
            sidebarCollapsed: localStorage.getItem('sidebar_collapsed') === 'true',
            mobileSidebarOpen: false,
            toggleSidebar() {
                document.documentElement.classList.add('sidebar-animated');
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('sidebar_collapsed', this.sidebarCollapsed);
                document.documentElement.classList.toggle('sidebar-collapsed', this.sidebarCollapsed);
                setTimeout(() => {
                    window.dispatchEvent(new Event('resize'));
                    document.documentElement.classList.remove('sidebar-animated');
                }, 260);
            },
            toggleMobileSidebar() {
                this.mobileSidebarOpen = !this.mobileSidebarOpen;
            },
            closeMobileSidebar() {
                this.mobileSidebarOpen = false;
            }
        }"
        @keydown.escape.window="closeMobileSidebar()"
        class="min-h-full">

        <x-sidebar></x-sidebar>

        <div class="satudata-content-wrapper flex min-h-screen flex-col">
            <x-topbar :title="$title ?? ''"></x-topbar>

            <main class="flex-1">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </main>
            <x-footer></x-footer>
        </div>
    </div>

    <x-tirta-agent-chat></x-tirta-agent-chat>

    @stack('scripts')
</body>

</html>
