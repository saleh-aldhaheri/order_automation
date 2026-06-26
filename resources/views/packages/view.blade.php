@php
    use App\Enums\PackageStatusEnum;

    $statusBadge = fn (?string $status) => match (PackageStatusEnum::tryFrom((string) $status)) {
        PackageStatusEnum::DELIVERED => 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300',
        PackageStatusEnum::SHIPPED => 'bg-indigo-500/15 text-indigo-800 dark:text-indigo-300',
        PackageStatusEnum::READY => 'bg-sky-500/15 text-sky-800 dark:text-sky-300',
        PackageStatusEnum::PENDING => 'bg-amber-500/15 text-amber-900 dark:text-amber-200',
        PackageStatusEnum::FAILED, PackageStatusEnum::LOST, PackageStatusEnum::CANCELLED => 'bg-red-500/15 text-red-800 dark:text-red-300',
        default => 'bg-slate-500/15 text-slate-700 dark:text-slate-300',
    };

    $tracking = array_filter((array) data_get($package->details, 'tracking_number', []));
@endphp

<x-layout title="{{ __('Package') }} {{ $package->external_package_id }} — {{ config('app.name') }}">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <a
                href="{{ route('packages.index') }}"
                class="text-sm font-semibold text-on-surface-variant transition hover:text-primary hover:underline"
            >
                ← {{ __('Back to packages') }}
            </a>
            <h1 class="font-headline mt-2 break-words text-2xl font-bold tracking-tight text-on-surface sm:text-3xl">
                {{ __('Package') }} {{ $package->external_package_id }}
            </h1>
            <p class="mt-1 text-sm text-on-surface-variant">
                {{ ucfirst($package->shop_type?->value ?? '—') }}
                · {{ __('Order') }}
                @if ($package->order)
                    <a href="{{ route('orders.show', $package->order) }}" class="font-semibold text-primary hover:underline">
                        {{ $package->external_order_id }}
                    </a>
                @else
                    {{ $package->external_order_id }}
                @endif
            </p>
        </div>
        <div class="flex shrink-0 flex-col items-stretch gap-3 sm:items-end">
            <span
                class="inline-flex justify-center rounded-full px-3 py-1 text-sm font-semibold {{ $statusBadge($package->package_status) }}"
            >
                {{ ucfirst($package->package_status ?? '—') }}
            </span>
            <form method="post" action="{{ route('packages.sync', $package) }}">
                @csrf
                <button
                    type="submit"
                    class="font-headline inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-on-primary shadow-sm transition hover:bg-primary-dim active:scale-[0.98]"
                >
                    <x-lumina.icon name="sync" class="!text-base" />
                    {{ __('Sync package') }}
                </button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <dl class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        @foreach ([
            __('Internal status') => ucfirst($package->package_status ?? '—'),
            __('Marketplace status') => $package->external_package_status ?: '—',
            __('Tracking #') => $tracking ? implode(', ', $tracking) : '—',
            __('Last updated') => $package->updated_at?->diffForHumans() ?? '—',
        ] as $label => $value)
            <div class="rounded-xl border border-outline-variant bg-surface-container px-4 py-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-on-surface-variant">{{ $label }}</dt>
                <dd class="mt-1 break-words text-sm font-semibold text-on-surface">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>
</x-layout>
