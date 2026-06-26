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

<x-layout title="{{ __('Orders') }} — {{ config('app.name') }}">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-headline text-2xl font-bold tracking-tight text-on-surface sm:text-3xl">
                {{ __('Orders') }}
            </h1>
            <p class="mt-1 text-sm text-on-surface-variant">
                {{ __('Track marketplace orders and their fulfilment status.') }}
            </p>
        </div>
    </div>

    @if (session('success'))
        <div
            class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200"
            role="status"
        >
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div
            class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200"
            role="alert"
        >
            {{ session('error') }}
        </div>
    @endif

    <x-data-table>
        <x-slot:toolbar>
            <div class="flex min-w-0 flex-1 flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <p class="shrink-0 text-sm text-on-surface-variant">
                    @if ($orders->total() > 0)
                        {{ __('Showing') }}
                        <span class="font-semibold text-on-surface">{{ $orders->firstItem() }}</span>
                        –
                        <span class="font-semibold text-on-surface">{{ $orders->lastItem() }}</span>
                        {{ __('of') }}
                        <span class="font-semibold text-on-surface">{{ $orders->total() }}</span>
                    @else
                        {{ __('No records to show.') }}
                    @endif
                </p>
                <x-data-table.toolbar-filters
                    :action="route('orders.index')"
                    :search="$search"
                    :per-page="$perPage"
                    :per-page-options="$perPageOptions"
                    :placeholder="__('Order #, shop, or status…')"
                    input-id="orders-table-search"
                    class="min-w-0 lg:max-w-2xl"
                />
            </div>
        </x-slot:toolbar>

        <x-data-table.table>
            <x-data-table.thead>
                <tr>
                    <x-data-table.th>{{ __('Order #') }}</x-data-table.th>
                    <x-data-table.th>{{ __('Marketplace') }}</x-data-table.th>
                    <x-data-table.th>{{ __('Status') }}</x-data-table.th>
                    <x-data-table.th align="center">{{ __('Packages') }}</x-data-table.th>
                    <x-data-table.th>{{ __('Updated') }}</x-data-table.th>
                    <x-data-table.th align="end">{{ __('Actions') }}</x-data-table.th>
                </tr>
            </x-data-table.thead>
            <x-data-table.tbody>
                @forelse ($orders as $row)
                    <x-data-table.row>
                        <x-data-table.td class="font-medium text-on-surface">
                            {{ $row->external_order_id }}
                        </x-data-table.td>
                        <x-data-table.td class="text-on-surface-variant">
                            <span class="block">{{ ucfirst($row->shop_type?->value ?? '—') }}</span>
                            @if ($row->external_shop_id)
                                <span class="text-xs text-on-surface-variant/70">{{ $row->external_shop_id }}</span>
                            @endif
                        </x-data-table.td>
                        <x-data-table.td>
                            <span
                                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusBadge($row->order_status) }}"
                            >
                                {{ ucfirst($row->order_status?->value ?? '—') }}
                            </span>
                            @if ($row->external_order_status)
                                <span class="mt-1 block text-xs text-on-surface-variant/70">
                                    {{ $row->external_order_status }}
                                </span>
                            @endif
                        </x-data-table.td>
                        <x-data-table.td align="center" class="text-on-surface-variant">
                            {{ $row->packages->count() }}
                        </x-data-table.td>
                        <x-data-table.td class="text-on-surface-variant">
                            {{ $row->updated_at?->diffForHumans() ?? '—' }}
                        </x-data-table.td>
                        <x-data-table.td align="end">
                            <a
                                href="{{ route('orders.show', $row) }}"
                                class="text-sm font-semibold text-primary hover:underline"
                            >
                                {{ __('View') }}
                            </a>
                        </x-data-table.td>
                    </x-data-table.row>
                @empty
                    <x-data-table.row>
                        <x-data-table.td colspan="6" empty>
                            @if (($search ?? '') !== '')
                                {{ __('No orders match your search.') }}
                            @else
                                {{ __('No orders yet. They appear here once marketplace webhooks sync.') }}
                            @endif
                        </x-data-table.td>
                    </x-data-table.row>
                @endforelse
            </x-data-table.tbody>
        </x-data-table.table>
    </x-data-table>

    <x-data-table.pagination>
        {{ $orders->links() }}
    </x-data-table.pagination>
</x-layout>
