@props([])

<div
    {{ $attributes->class([
        'w-full min-w-0 overflow-hidden rounded-xl border border-outline-variant/15 bg-surface-container-lowest shadow-sm ring-1 ring-outline-variant/5',
    ]) }}
>
    @if (isset($toolbar) && ! $toolbar->isEmpty())
        <div
            class="flex flex-col gap-3 border-b border-outline-variant/15 bg-surface-container-low px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
        >
            {{ $toolbar }}
        </div>
    @endif

    <div class="overflow-x-auto">
        {{ $slot }}
    </div>
</div>
