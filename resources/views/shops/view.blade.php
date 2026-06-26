<x-layout title="{{ __('Shop') }} {{ $shop->external_shop_id }} — {{ config('app.name') }}">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <a
                href="{{ route('shops.index') }}"
                class="text-sm font-semibold text-on-surface-variant transition hover:text-primary hover:underline"
            >
                ← {{ __('Back to shops') }}
            </a>
            <h1 class="font-headline mt-2 break-words text-2xl font-bold tracking-tight text-on-surface sm:text-3xl">
                {{ ucfirst($shop->shop_type?->value ?? '—') }}
            </h1>
            <p class="mt-1 text-sm text-on-surface-variant">
                {{ __('Shop') }} {{ $shop->external_shop_id }}
            </p>
        </div>
        @if ($shop->is_active)
            <span class="inline-flex shrink-0 rounded-full bg-emerald-500/15 px-3 py-1 text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                {{ __('Active') }}
            </span>
        @else
            <span class="inline-flex shrink-0 rounded-full bg-slate-500/15 px-3 py-1 text-sm font-semibold text-slate-700 dark:text-slate-300">
                {{ __('Inactive') }}
            </span>
        @endif
    </div>

    <dl class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        @foreach ([
            __('Marketplace') => ucfirst($shop->shop_type?->value ?? '—'),
            __('Orders') => $shop->orders_count,
            __('Connected') => $shop->created_at?->diffForHumans() ?? '—',
            __('Last updated') => $shop->updated_at?->diffForHumans() ?? '—',
        ] as $label => $value)
            <div class="rounded-xl border border-outline-variant bg-surface-container px-4 py-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-on-surface-variant">{{ $label }}</dt>
                <dd class="mt-1 break-words text-sm font-semibold text-on-surface">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>
</x-layout>
