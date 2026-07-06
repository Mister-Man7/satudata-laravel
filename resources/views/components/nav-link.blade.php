@props(['active' => false, 'mobile' => false])

<a {{ $attributes->class([
    'rounded-md px-3 py-2 font-medium',
    'block w-full text-base' => $mobile,
    'text-sm' => !$mobile,
    'bg-gray-900 text-white' => $active,
    'text-gray-300 hover:bg-white/5 hover:text-white' => !$active,
]) }}
    @if ($active) aria-current="page" @endif>{{ $slot }}
</a>
