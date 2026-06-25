<title>{{ $title ?? 'SATUDATA UNTIRTA' }}</title>
<x-layout>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <div
                class="inline-flex items-center rounded-full bg-blue-50 px-4 py-2 text-xs font-bold tracking-wide text-blue-600 ring-1 ring-blue-100">
                DASHBOARD AKADEMIK
            </div>

            <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">
                Ringkasan Data Mahasiswa
            </h1>

            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                Data ringkasan mahasiswa berdasarkan status akademik, kelulusan, dan periode masuk.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($stats as $stat)
                <x-ui.akademik-card
                    :title="$stat['title']"
                    :value="$stat['value']"
                    :description="$stat['description']"
                    :status="$stat['status']"
                    :href="$stat['href']"
                    :icon-bg="$stat['iconBg']"
                    :icon-color="$stat['iconColor']"
                />
            @endforeach
        </div>
    </section>
</x-layout>
