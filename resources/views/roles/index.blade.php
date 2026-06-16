<x-layout title="{{ __('Roles') }} — {{ config('app.name') }}">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-headline text-2xl font-bold tracking-tight text-on-surface sm:text-3xl">
                    {{ __('Roles') }}
                </h1>
                <p class="mt-1 text-sm text-on-surface-variant">
                    {{ __('Define roles and attach permissions. Users receive access through their role.') }}
                </p>
            </div>
            <a
                href="{{ route('roles.create') }}"
                class="font-headline inline-flex items-center justify-center rounded-xl bg-primary px-5 py-3 text-sm font-bold text-on-primary shadow-sm transition hover:bg-primary-dim active:scale-[0.98]"
            >
                {{ __('New role') }}
            </a>
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

        @if ($errors->has('_role'))
            <div
                class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200"
                role="alert"
            >
                {{ $errors->first('_role') }}
            </div>
        @endif

        <x-data-table>
            <x-slot:toolbar>
                <div class="flex min-w-0 flex-1 flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <p class="shrink-0 text-sm text-on-surface-variant">
                        @if ($roles->total() > 0)
                            {{ __('Showing') }}
                            <span class="font-semibold text-on-surface">{{ $roles->firstItem() }}</span>
                            –
                            <span class="font-semibold text-on-surface">{{ $roles->lastItem() }}</span>
                            {{ __('of') }}
                            <span class="font-semibold text-on-surface">{{ $roles->total() }}</span>
                        @else
                            {{ __('No records to show.') }}
                        @endif
                    </p>
                    <x-data-table.toolbar-filters
                        :action="route('roles.index')"
                        :search="$search"
                        :per-page="$perPage"
                        :per-page-options="$perPageOptions"
                        :placeholder="__('Role name…')"
                        input-id="roles-table-search"
                        class="min-w-0 lg:max-w-2xl"
                    />
                </div>
            </x-slot:toolbar>

            <x-data-table.table>
                <x-data-table.thead>
                    <tr>
                        <x-data-table.th>{{ __('Role') }}</x-data-table.th>
                        <x-data-table.th>{{ __('Permissions') }}</x-data-table.th>
                        <x-data-table.th align="end">{{ __('Actions') }}</x-data-table.th>
                    </tr>
                </x-data-table.thead>
                <x-data-table.tbody>
                    @forelse ($roles as $roleRow)
                        <x-data-table.row>
                            <x-data-table.td class="font-medium text-on-surface">
                                {{ $roleRow->name }}
                            </x-data-table.td>
                            <x-data-table.td class="text-on-surface-variant">
                                {{ $roleRow->permissions_count }}
                                {{ $roleRow->permissions_count === 1 ? __('permission') : __('permissions') }}
                            </x-data-table.td>
                            <x-data-table.td align="end">
                                <div class="flex flex-wrap items-center justify-end gap-3">
                                    <a
                                        href="{{ route('roles.edit', $roleRow) }}"
                                        class="text-sm font-semibold text-primary hover:underline"
                                    >
                                        {{ __('Edit') }}
                                    </a>
                                    <form
                                        action="{{ route('roles.destroy', $roleRow) }}"
                                        method="post"
                                        class="inline"
                                        onsubmit="return confirm(@json(__('Delete this role?')));"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="text-sm font-semibold text-error hover:underline"
                                        >
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                </div>
                            </x-data-table.td>
                        </x-data-table.row>
                    @empty
                        <x-data-table.row>
                            <x-data-table.td colspan="3" empty>
                                @if (($search ?? '') !== '')
                                    {{ __('No roles match your search.') }}
                                @else
                                    {{ __('No roles yet. Create one to get started.') }}
                                @endif
                            </x-data-table.td>
                        </x-data-table.row>
                    @endforelse
                </x-data-table.tbody>
            </x-data-table.table>
        </x-data-table>

    <x-data-table.pagination>
        {{ $roles->links() }}
    </x-data-table.pagination>
</x-layout>
