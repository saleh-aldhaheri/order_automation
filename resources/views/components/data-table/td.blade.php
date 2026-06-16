@props([
    'align' => 'start',
    'colspan' => null,
    /** Use for full-width empty state row */
    'empty' => false,
])

@php
    $alignClass = match ($align) {
        'end' => 'text-end',
        'center' => 'text-center',
        default => 'text-start',
    };

    $padding = $empty
        ? 'px-4 py-12 text-on-surface-variant'
        : 'min-w-0 px-4 py-3 break-words text-on-surface';
@endphp

<td
    @if ($colspan !== null)
        colspan="{{ $colspan }}"
    @endif
    {{ $attributes->class([$alignClass, $padding, $empty ? 'text-center' : '']) }}
>
    {{ $slot }}
</td>
