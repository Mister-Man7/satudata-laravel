<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>
        {{ $title ?? ucwords(str_replace('-', ' ', request()->segment(count(request()->segments())) ?? 'dashboard')) }}
        | SATUDATA UNTIRTA</title>

    <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    {{--    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>--}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full">

<div class="min-h-full">
    <x-navbar></x-navbar>

    <x-header>{{ $title }}</x-header>

    <main>
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    </main>
    <x-footer></x-footer>
</div>

<x-tirta-agent-chat></x-tirta-agent-chat>

</body>

</html>
