<x-layout title="{{ __('Shops') }} — {{ config('app.name') }}">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-headline text-2xl font-bold tracking-tight text-on-surface sm:text-3xl">
                {{ __('Shops') }}
            </h1>
            <p class="mt-1 text-sm text-on-surface-variant">
                {{ __('Connected marketplace shops and their authorization status.') }}
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
                    @if ($shops->total() > 0)
                        {{ __('Showing') }}
                        <span class="font-semibold text-on-surface">{{ $shops->firstItem() }}</span>
                        –
                        <span class="font-semibold text-on-surface">{{ $shops->lastItem() }}</span>
                        {{ __('of') }}
                        <span class="font-semibold text-on-surface">{{ $shops->total() }}</span>
                    @else
                        {{ __('No records to show.') }}
                    @endif
                </p>
                <x-data-table.toolbar-filters
                    :action="route('shops.index')"
                    :search="$search"
                    :per-page="$perPage"
                    :per-page-options="$perPageOptions"
                    :placeholder="__('Marketplace or shop ID…')"
                    input-id="shops-table-search"
                    class="min-w-0 lg:max-w-2xl"
                />
            </div>
        </x-slot:toolbar>

        <x-data-table.table>
            <x-data-table.thead>
                <tr>
                    <x-data-table.th>{{ __('Marketplace') }}</x-data-table.th>
                    <x-data-table.th>{{ __('Shop ID') }}</x-data-table.th>
                    <x-data-table.th>{{ __('Status') }}</x-data-table.th>
                    <x-data-table.th align="center">{{ __('Orders') }}</x-data-table.th>
                    <x-data-table.th>{{ __('Connected') }}</x-data-table.th>
                    <x-data-table.th align="end">{{ __('Actions') }}</x-data-table.th>
                </tr>
            </x-data-table.thead>
            <x-data-table.tbody>
                @forelse ($shops as $row)
                    <x-data-table.row>
                        <x-data-table.td class="font-medium text-on-surface">
                            {{ ucfirst($row->shop_type?->value ?? '—') }}
                        </x-data-table.td>
                        <x-data-table.td class="text-on-surface-variant">
                            {{ $row->external_shop_id }}
                        </x-data-table.td>
                        <x-data-table.td>
                            @if ($row->is_active)
                                <span class="inline-flex rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:text-emerald-300">
                                    {{ __('Active') }}
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-500/15 px-2.5 py-0.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    {{ __('Inactive') }}
                                </span>
                            @endif
                        </x-data-table.td>
                        <x-data-table.td align="center" class="text-on-surface-variant">
                            {{ $row->orders_count }}
                        </x-data-table.td>
                        <x-data-table.td class="text-on-surface-variant">
                            {{ $row->created_at?->diffForHumans() ?? '—' }}
                        </x-data-table.td>
                        <x-data-table.td align="end">
                            <a
                                href="{{ route('shops.show', $row) }}"
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
                                {{ __('No shops match your search.') }}
                            @else
                                {{ __('No shops connected yet.') }}
                            @endif
                        </x-data-table.td>
                    </x-data-table.row>
                @endforelse
            </x-data-table.tbody>
        </x-data-table.table>
    </x-data-table>

    <x-data-table.pagination>
        {{ $shops->links() }}
    </x-data-table.pagination>
</x-layout>
