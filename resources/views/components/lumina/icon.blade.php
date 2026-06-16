@props([
    'name',
    'filled' => false,
])
<span
    {{ $attributes->class(['material-symbols-outlined align-middle']) }}
    style="{{ $filled ? "font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24" : "font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24" }}"
>{{ $name }}</span>
