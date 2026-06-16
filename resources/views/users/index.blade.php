<x-layout title="{{ __('Users') }} — {{ config('app.name') }}">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-headline text-2xl font-bold tracking-tight text-on-surface sm:text-3xl">
                    {{ __('Users') }}
                </h1>
                <p class="mt-1 text-sm text-on-surface-variant">
                    {{ __('Invite teammates, assign roles, and keep access up to date.') }}
                </p>
            </div>
            <a
                href="{{ route('users.create') }}"
                class="font-headline inline-flex items-center justify-center rounded-xl bg-primary px-5 py-3 text-sm font-bold text-on-primary shadow-sm transition hover:bg-primary-dim active:scale-[0.98]"
            >
                {{ __('Invite user') }}
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

        @if ($errors->any())
            <div
                class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200"
                role="alert"
            >
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-data-table>
            <x-slot:toolbar>
                <div class="flex min-w-0 flex-1 flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <p class="shrink-0 text-sm text-on-surface-variant">
                        @if ($users->total() > 0)
                            {{ __('Showing') }}
                            <span class="font-semibold text-on-surface">{{ $users->firstItem() }}</span>
                            –
                            <span class="font-semibold text-on-surface">{{ $users->lastItem() }}</span>
                            {{ __('of') }}
                            <span class="font-semibold text-on-surface">{{ $users->total() }}</span>
                        @else
                            {{ __('No records to show.') }}
                        @endif
                    </p>
                    <x-data-table.toolbar-filters
                        :action="route('users.index')"
                        :search="$search"
                        :per-page="$perPage"
                        :per-page-options="$perPageOptions"
                        :placeholder="__('Name, email, or dates…')"
                        input-id="users-table-search"
                        class="min-w-0 lg:max-w-2xl"
                    />
                </div>
            </x-slot:toolbar>

            <x-data-table.table>
                <x-data-table.thead>
                    <tr>
                        <x-data-table.th>{{ __('Name') }}</x-data-table.th>
                        <x-data-table.th>{{ __('Email') }}</x-data-table.th>
                        <x-data-table.th>{{ __('Role') }}</x-data-table.th>
                        <x-data-table.th>{{ __('Status') }}</x-data-table.th>
                        <x-data-table.th align="end">{{ __('Actions') }}</x-data-table.th>
                    </tr>
                </x-data-table.thead>
                <x-data-table.tbody>
                    @forelse ($users as $row)
                        <x-data-table.row>
                            <x-data-table.td class="font-medium text-on-surface">
                                {{ $row->name }}
                            </x-data-table.td>
                            <x-data-table.td class="text-on-surface-variant">
                                {{ $row->email }}
                            </x-data-table.td>
                            <x-data-table.td class="text-on-surface-variant">
                                {{ $row->roles->pluck('name')->join(', ') ?: '—' }}
                            </x-data-table.td>
                            <x-data-table.td>
                                @if ($row->email_verified_at)
                                    <span
                                        class="inline-flex rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:text-emerald-300"
                                    >
                                        {{ __('Verified') }}
                                    </span>
                                @else
                                    <span
                                        class="inline-flex rounded-full bg-amber-500/15 px-2.5 py-0.5 text-xs font-semibold text-amber-900 dark:text-amber-200"
                                    >
                                        {{ __('Pending') }}
                                    </span>
                                @endif
                            </x-data-table.td>
                            <x-data-table.td align="end">
                                <div class="flex flex-wrap items-center justify-end gap-3">
                                    <a
                                        href="{{ route('users.show', $row) }}"
                                        class="text-sm font-semibold text-on-surface-variant transition hover:text-primary hover:underline"
                                    >
                                        {{ __('View') }}
                                    </a>
                                    <a
                                        href="{{ route('users.edit', $row) }}"
                                        class="text-sm font-semibold text-primary hover:underline"
                                    >
                                        {{ __('Edit') }}
                                    </a>
                                    <form
                                        action="{{ route('users.destroy', $row) }}"
                                        method="post"
                                        class="inline"
                                        onsubmit="return confirm(@json(__('Delete this user?')));"
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
                            <x-data-table.td colspan="5" empty>
                                @if (($search ?? '') !== '')
                                    {{ __('No users match your search.') }}
                                @else
                                    {{ __('No users yet. Invite someone to get started.') }}
                                @endif
                            </x-data-table.td>
                        </x-data-table.row>
                    @endforelse
                </x-data-table.tbody>
            </x-data-table.table>
        </x-data-table>

    <x-data-table.pagination>
        {{ $users->links() }}
    </x-data-table.pagination>
</x-layout>
