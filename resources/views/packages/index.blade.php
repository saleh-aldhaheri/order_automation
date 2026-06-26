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
@endphp

<x-layout title="{{ __('Packages') }} — {{ config('app.name') }}">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-headline text-2xl font-bold tracking-tight text-on-surface sm:text-3xl">
                {{ __('Packages') }}
            </h1>
            <p class="mt-1 text-sm text-on-surface-variant">
                {{ __('Track parcels and their fulfilment status across orders.') }}
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
                    @if ($packages->total() > 0)
                        {{ __('Showing') }}
                        <span class="font-semibold text-on-surface">{{ $packages->firstItem() }}</span>
                        –
                        <span class="font-semibold text-on-surface">{{ $packages->lastItem() }}</span>
                        {{ __('of') }}
                        <span class="font-semibold text-on-surface">{{ $packages->total() }}</span>
                    @else
                        {{ __('No records to show.') }}
                    @endif
                </p>
                <x-data-table.toolbar-filters
                    :action="route('packages.index')"
                    :search="$search"
                    :per-page="$perPage"
                    :per-page-options="$perPageOptions"
                    :placeholder="__('Package #, order #, or status…')"
                    input-id="packages-table-search"
                    class="min-w-0 lg:max-w-2xl"
                />
            </div>
        </x-slot:toolbar>

        <x-data-table.table>
            <x-data-table.thead>
                <tr>
                    <x-data-table.th>{{ __('Package #') }}</x-data-table.th>
                    <x-data-table.th>{{ __('Order #') }}</x-data-table.th>
                    <x-data-table.th>{{ __('Marketplace') }}</x-data-table.th>
                    <x-data-table.th>{{ __('Status') }}</x-data-table.th>
                    <x-data-table.th>{{ __('Updated') }}</x-data-table.th>
                    <x-data-table.th align="end">{{ __('Actions') }}</x-data-table.th>
                </tr>
            </x-data-table.thead>
            <x-data-table.tbody>
                @forelse ($packages as $row)
                    <x-data-table.row>
                        <x-data-table.td class="font-medium text-on-surface">
                            {{ $row->external_package_id }}
                        </x-data-table.td>
                        <x-data-table.td class="text-on-surface-variant">
                            @if ($row->order)
                                <a
                                    href="{{ route('orders.show', $row->order) }}"
                                    class="font-semibold text-primary hover:underline"
                                >
                                    {{ $row->external_order_id }}
                                </a>
                            @else
                                {{ $row->external_order_id }}
                            @endif
                        </x-data-table.td>
                        <x-data-table.td class="text-on-surface-variant">
                            {{ ucfirst($row->shop_type?->value ?? '—') }}
                        </x-data-table.td>
                        <x-data-table.td>
                            <span
                                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusBadge($row->package_status) }}"
                            >
                                {{ ucfirst($row->package_status ?? '—') }}
                            </span>
                            @if ($row->external_package_status)
                                <span class="mt-1 block text-xs text-on-surface-variant/70">
                                    {{ $row->external_package_status }}
                                </span>
                            @endif
                        </x-data-table.td>
                        <x-data-table.td class="text-on-surface-variant">
                            {{ $row->updated_at?->diffForHumans() ?? '—' }}
                        </x-data-table.td>
                        <x-data-table.td align="end">
                            <a
                                href="{{ route('packages.show', $row) }}"
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
                                {{ __('No packages match your search.') }}
                            @else
                                {{ __('No packages yet. They appear here once orders are synced.') }}
                            @endif
                        </x-data-table.td>
                    </x-data-table.row>
                @endforelse
            </x-data-table.tbody>
        </x-data-table.table>
    </x-data-table>

    <x-data-table.pagination>
        {{ $packages->links() }}
    </x-data-table.pagination>
</x-layout>
