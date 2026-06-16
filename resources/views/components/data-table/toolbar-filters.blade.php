@props([
    'action',
    'search' => '',
    'perPage' => 5,
    'perPageOptions' => [5, 10, 25, 50, 100],
    'placeholder' => null,
    'inputId' => 'table-search',
])

@php
    $searchValue = old('search', $search ?? '');
    $searchValue = is_string($searchValue) ? $searchValue : '';
    $perPage = (int) $perPage;
    $clearUrl = $action.(str_contains($action, '?') ? '&' : '?').http_build_query(['per_page' => $perPage]);
@endphp

<form
    method="get"
    action="{{ $action }}"
    {{ $attributes->class('flex min-w-0 flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end') }}
    role="search"
>
    <div class="flex w-full min-w-0 flex-1 flex-col gap-1.5 sm:max-w-md lg:max-w-lg">
        <label for="{{ $inputId }}" class="text-sm font-medium text-on-surface">{{ __('Search') }}</label>
        <div class="flex flex-wrap items-stretch gap-2">
            <input
                type="search"
                name="search"
                id="{{ $inputId }}"
                value="{{ $searchValue }}"
                placeholder="{{ $placeholder ?? __('Search…') }}"
                autocomplete="off"
                class="min-w-0 flex-1 rounded-xl border-none bg-surface-container-low px-3 py-2 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none placeholder:text-on-surface-variant/60 focus:ring-2 focus:ring-primary/25"
            />
            <button
                type="submit"
                class="font-headline inline-flex shrink-0 items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-bold text-on-primary shadow-sm transition hover:bg-primary-dim active:scale-[0.98]"
            >
                {{ __('Apply') }}
            </button>
            @if ($searchValue !== '')
                <a
                    href="{{ $clearUrl }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-xl border border-outline-variant/25 bg-surface-container-low px-4 py-2 text-sm font-semibold text-on-surface transition hover:bg-surface-container-high"
                >
                    {{ __('Clear') }}
                </a>
            @endif
        </div>
    </div>
    <div class="flex shrink-0 items-center gap-2">
        <label for="per_page_{{ md5($action.$inputId) }}" class="whitespace-nowrap text-sm font-medium text-on-surface">
            {{ __('Rows per page') }}
        </label>
        <select
            id="per_page_{{ md5($action.$inputId) }}"
            name="per_page"
            onchange="this.form.submit()"
            class="rounded-xl border-none bg-surface-container-low px-2.5 py-1.5 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none focus:ring-2 focus:ring-primary/25"
        >
            @foreach ($perPageOptions as $n)
                <option value="{{ $n }}" @selected($perPage === (int) $n)>{{ $n }}</option>
            @endforeach
        </select>
    </div>
</form>
