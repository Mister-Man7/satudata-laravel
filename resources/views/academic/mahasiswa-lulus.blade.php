<x-layout>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('akademik') }}" class="text-sm font-bold text-blue-600 hover:text-blue-700">
                ← Kembali ke Akademik
            </a>

            <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-gray-900">
                Detail Mahasiswa Lulus
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Filter data mahasiswa lulus berdasarkan tahun lulus, angkatan, program studi, atau pencarian nama/NIM.
            </p>
        </div>

        <livewire:mahasiswa-lulus-table />
    </section>
</x-layout>
