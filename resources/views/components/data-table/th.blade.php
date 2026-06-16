@props([
    'align' => 'start',
])

@php
    $alignClass = match ($align) {
        'end' => 'text-end',
        'center' => 'text-center',
        default => 'text-start',
    };
@endphp

<th
    scope="col"
    {{ $attributes->class([
        'min-w-0 px-4 py-3 font-medium text-on-surface-variant',
        $alignClass,
    ]) }}
>
    {{ $slot }}
</th>
