@php
    use App\Enums\OrderStatusEnum;

    $statusBadge = fn (?OrderStatusEnum $status) => match ($status) {
        OrderStatusEnum::PROCESSED => 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300',
        OrderStatusEnum::UNPROCESSED => 'bg-amber-500/15 text-amber-900 dark:text-amber-200',
        OrderStatusEnum::CANCELLED => 'bg-red-500/15 text-red-800 dark:text-red-300',
        OrderStatusEnum::RETURNING => 'bg-indigo-500/15 text-indigo-800 dark:text-indigo-300',
        default => 'bg-slate-500/15 text-slate-700 dark:text-slate-300',
    };
@endphp

<x-layout title="{{ __('Order') }} {{ $order->external_order_id }} — {{ config('app.name') }}">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <a
                href="{{ route('orders.index') }}"
                class="text-sm font-semibold text-on-surface-variant transition hover:text-primary hover:underline"
            >
                ← {{ __('Back to orders') }}
            </a>
            <h1 class="font-headline mt-2 break-words text-2xl font-bold tracking-tight text-on-surface sm:text-3xl">
                {{ __('Order') }} {{ $order->external_order_id }}
            </h1>
            <p class="mt-1 text-sm text-on-surface-variant">
                {{ ucfirst($order->shop_type?->value ?? '—') }}
                @if ($order->shop)
                    · <a href="{{ route('shops.show', $order->shop) }}" class="font-semibold text-primary hover:underline">{{ __('Shop') }} {{ $order->external_shop_id }}</a>
                @elseif ($order->external_shop_id)
                    · {{ __('Shop') }} {{ $order->external_shop_id }}
                @endif
            </p>
        </div>
        <div class="flex shrink-0 flex-col items-stretch gap-3 sm:items-end">
            <span
                class="inline-flex justify-center rounded-full px-3 py-1 text-sm font-semibold {{ $statusBadge($order->order_status) }}"
            >
                {{ ucfirst($order->order_status?->value ?? '—') }}
            </span>
            <form method="post" action="{{ route('orders.sync', $order) }}">
                @csrf
                <button
                    type="submit"
                    class="font-headline inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-on-primary shadow-sm transition hover:bg-primary-dim active:scale-[0.98]"
                >
                    <x-lumina.icon name="sync" class="!text-base" />
                    {{ __('Sync order status') }}
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
            __('Internal status') => ucfirst($order->order_status?->value ?? '—'),
            __('Marketplace status') => $order->external_order_status ?: '—',
            __('Packages') => $order->packages->count(),
            __('Last updated') => $order->updated_at?->diffForHumans() ?? '—',
        ] as $label => $value)
            <div class="rounded-xl border border-outline-variant bg-surface-container px-4 py-3">
                <dt class="text-xs font-medium uppercase tracking-wide text-on-surface-variant">{{ $label }}</dt>
                <dd class="mt-1 break-words text-sm font-semibold text-on-surface">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>

    <section>
        <h2 class="font-headline mb-4 text-lg font-bold text-on-surface">{{ __('Packages') }}</h2>

        <x-data-table>
            <x-data-table.table>
                <x-data-table.thead>
                    <tr>
                        <x-data-table.th>{{ __('Package #') }}</x-data-table.th>
                        <x-data-table.th>{{ __('Status') }}</x-data-table.th>
                        <x-data-table.th>{{ __('Marketplace status') }}</x-data-table.th>
                        <x-data-table.th>{{ __('Tracking #') }}</x-data-table.th>
                    </tr>
                </x-data-table.thead>
                <x-data-table.tbody>
                    @forelse ($order->packages as $package)
                        <x-data-table.row>
                            <x-data-table.td class="font-medium text-on-surface">
                                <a href="{{ route('packages.show', $package) }}" class="text-primary hover:underline">
                                    {{ $package->external_package_id }}
                                </a>
                            </x-data-table.td>
                            <x-data-table.td class="text-on-surface-variant">
                                {{ ucfirst($package->package_status ?? '—') }}
                            </x-data-table.td>
                            <x-data-table.td class="text-on-surface-variant">
                                {{ $package->external_package_status ?: '—' }}
                            </x-data-table.td>
                            <x-data-table.td class="text-on-surface-variant">
                                @php $tracking = array_filter((array) data_get($package->details, 'tracking_number', [])); @endphp
                                {{ $tracking ? implode(', ', $tracking) : '—' }}
                            </x-data-table.td>
                        </x-data-table.row>
                    @empty
                        <x-data-table.row>
                            <x-data-table.td colspan="4" empty>
                                {{ __('No packages for this order yet.') }}
                            </x-data-table.td>
                        </x-data-table.row>
                    @endforelse
                </x-data-table.tbody>
            </x-data-table.table>
        </x-data-table>
    </section>
</x-layout>
