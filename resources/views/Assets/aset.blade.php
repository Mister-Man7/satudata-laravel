<x-layout>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>
    <x-ui.aset-card :datas="$datas" :level="$level ?? 'bmn'"></x-ui.aset-card>
</x-layout>
