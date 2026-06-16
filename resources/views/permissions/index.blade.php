@php
    use App\Support\PermissionLabel;
@endphp

<x-layout title="{{ __('Permissions') }} — {{ config('app.name') }}">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-headline text-2xl font-bold tracking-tight text-on-surface sm:text-3xl">
                    {{ __('Permissions') }}
                </h1>
                <p class="mt-1 text-sm text-on-surface-variant">
                    {{ __('Manage permission names and guards. Changes apply to Spatie roles on next request.') }}
                </p>
            </div>
            <button
                type="button"
                onclick="document.getElementById('create-permission-dialog').showModal()"
                class="font-headline inline-flex items-center justify-center rounded-xl bg-primary px-5 py-3 text-sm font-bold text-on-primary shadow-sm transition hover:bg-primary-dim active:scale-[0.98]"
            >
                {{ __('Add permission') }}
            </button>
        </div>

        @if (session('success'))
            <div
                class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200"
                role="status"
            >
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('_permission'))
            <div
                class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200"
                role="alert"
            >
                {{ $errors->first('_permission') }}
            </div>
        @endif

        <x-data-table>
            <x-slot:toolbar>
                <div class="flex min-w-0 flex-1 flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <p class="shrink-0 text-sm text-on-surface-variant">
                        @if ($permissions->total() > 0)
                            {{ __('Showing') }}
                            <span class="font-semibold text-on-surface">{{ $permissions->firstItem() }}</span>
                            –
                            <span class="font-semibold text-on-surface">{{ $permissions->lastItem() }}</span>
                            {{ __('of') }}
                            <span class="font-semibold text-on-surface">{{ $permissions->total() }}</span>
                        @else
                            {{ __('No records to show.') }}
                        @endif
                    </p>
                    <x-data-table.toolbar-filters
                        :action="route('permissions.index')"
                        :search="$search"
                        :per-page="$perPage"
                        :per-page-options="$perPageOptions"
                        :placeholder="__('Permission name or guard…')"
                        input-id="permissions-table-search"
                        class="min-w-0 lg:max-w-2xl"
                    />
                </div>
            </x-slot:toolbar>

            <x-data-table.table>
                <x-data-table.thead>
                    <tr>
                        <x-data-table.th>{{ __('Permission') }}</x-data-table.th>
                        <x-data-table.th>{{ __('Guard') }}</x-data-table.th>
                        <x-data-table.th align="end">{{ __('Actions') }}</x-data-table.th>
                    </tr>
                </x-data-table.thead>
                <x-data-table.tbody>
                    @forelse ($permissions as $permission)
                        <x-data-table.row>
                            <x-data-table.td>
                                <div class="font-medium text-on-surface">
                                    {{ PermissionLabel::for($permission->name) }}
                                </div>
                                <div class="mt-0.5 font-mono text-xs text-on-surface-variant">
                                    {{ $permission->name }}
                                </div>
                            </x-data-table.td>
                            <x-data-table.td class="text-on-surface-variant">
                                {{ $permission->guard_name }}
                            </x-data-table.td>
                            <x-data-table.td align="end">
                                <div class="flex flex-wrap items-center justify-end gap-3">
                                    <button
                                        type="button"
                                        class="text-sm font-semibold text-primary hover:underline"
                                        data-edit-permission
                                        data-update-url="{{ route('permissions.update', $permission) }}"
                                        data-name="{{ $permission->name }}"
                                        data-guard="{{ $permission->guard_name }}"
                                    >
                                        {{ __('Edit') }}
                                    </button>
                                    <form
                                        action="{{ route('permissions.destroy', $permission) }}"
                                        method="post"
                                        class="inline"
                                        onsubmit="return confirm(@json(__('Delete this permission?')));"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-semibold text-error hover:underline">
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
                                    {{ __('No permissions match your search.') }}
                                @else
                                    {{ __('No permissions yet. Create one to get started.') }}
                                @endif
                            </x-data-table.td>
                        </x-data-table.row>
                    @endforelse
                </x-data-table.tbody>
            </x-data-table.table>
        </x-data-table>

    <x-data-table.pagination>
        {{ $permissions->links() }}
    </x-data-table.pagination>

    <dialog
        id="create-permission-dialog"
        class="w-[min(100vw-2rem,28rem)] max-w-lg rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-0 shadow-xl ring-1 ring-outline-variant/5 [&::backdrop]:bg-inverse-surface/50"
    >
        <form method="post" action="{{ route('permissions.store') }}" class="flex flex-col">
            @csrf
            <div class="border-b border-outline-variant/15 px-5 py-4">
                <h2 class="font-headline text-lg font-bold text-on-surface">{{ __('New permission') }}</h2>
                <p class="mt-0.5 text-sm text-on-surface-variant">
                    {{ __('Use resource:action (e.g. roles:create, user:view). Role UI groups by resource.') }}
                </p>
            </div>
            <div class="space-y-4 px-5 py-4">
                @if ($errors->any() && ! old('_permission_id'))
                    <ul class="list-inside list-disc space-y-1 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
                <div>
                    <label for="create-name" class="mb-1.5 block text-sm font-medium text-on-surface">{{ __('Name') }}</label>
                    <input
                        id="create-name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        maxlength="255"
                        class="w-full rounded-xl border-none bg-surface-container-low px-3 py-2 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none focus:ring-2 focus:ring-primary/25"
                        placeholder="role:view"
                        autocomplete="off"
                    />
                </div>
                <div>
                    <label for="create-guard" class="mb-1.5 block text-sm font-medium text-on-surface">{{ __('Guard') }}</label>
                    <input
                        id="create-guard"
                        name="guard_name"
                        value="{{ old('guard_name', 'web') }}"
                        maxlength="255"
                        class="w-full rounded-xl border-none bg-surface-container-low px-3 py-2 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none focus:ring-2 focus:ring-primary/25"
                        placeholder="web"
                    />
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-outline-variant/15 bg-surface-container-low/50 px-5 py-4">
                <button
                    type="button"
                    onclick="document.getElementById('create-permission-dialog').close()"
                    class="rounded-xl border border-outline-variant/20 bg-surface-container-lowest px-4 py-2 text-sm font-semibold text-on-surface transition hover:bg-surface-container-high"
                >
                    {{ __('Cancel') }}
                </button>
                <button
                    type="submit"
                    class="font-headline rounded-xl bg-primary px-4 py-2 text-sm font-bold text-on-primary transition hover:bg-primary-dim"
                >
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </dialog>

    <dialog
        id="edit-permission-dialog"
        class="w-[min(100vw-2rem,28rem)] max-w-lg rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-0 shadow-xl ring-1 ring-outline-variant/5 [&::backdrop]:bg-inverse-surface/50"
    >
        <form
            id="edit-permission-form"
            method="post"
            action="{{ old('_permission_id') ? route('permissions.update', old('_permission_id')) : '#' }}"
            class="flex flex-col"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="_permission_id" id="edit-permission-id" value="{{ old('_permission_id') }}" />
            <div class="border-b border-outline-variant/15 px-5 py-4">
                <h2 class="font-headline text-lg font-bold text-on-surface">{{ __('Edit permission') }}</h2>
            </div>
            <div class="space-y-4 px-5 py-4">
                @if ($errors->any() && old('_permission_id'))
                    <ul class="list-inside list-disc space-y-1 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
                <div>
                    <label for="edit-name" class="mb-1.5 block text-sm font-medium text-on-surface">{{ __('Name') }}</label>
                    <input
                        id="edit-name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        maxlength="255"
                        class="w-full rounded-xl border-none bg-surface-container-low px-3 py-2 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none focus:ring-2 focus:ring-primary/25"
                    />
                </div>
                <div>
                    <label for="edit-guard" class="mb-1.5 block text-sm font-medium text-on-surface">{{ __('Guard') }}</label>
                    <input
                        id="edit-guard"
                        name="guard_name"
                        value="{{ old('guard_name') }}"
                        maxlength="255"
                        class="w-full rounded-xl border-none bg-surface-container-low px-3 py-2 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none focus:ring-2 focus:ring-primary/25"
                    />
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-outline-variant/15 bg-surface-container-low/50 px-5 py-4">
                <button
                    type="button"
                    onclick="document.getElementById('edit-permission-dialog').close()"
                    class="rounded-xl border border-outline-variant/20 bg-surface-container-lowest px-4 py-2 text-sm font-semibold text-on-surface transition hover:bg-surface-container-high"
                >
                    {{ __('Cancel') }}
                </button>
                <button
                    type="submit"
                    class="font-headline rounded-xl bg-primary px-4 py-2 text-sm font-bold text-on-primary transition hover:bg-primary-dim"
                >
                    {{ __('Update') }}
                </button>
            </div>
        </form>
    </dialog>

    <script>
        (function () {
            const editDialog = document.getElementById('edit-permission-dialog');
            const editForm = document.getElementById('edit-permission-form');
            const editIdInput = document.getElementById('edit-permission-id');
            const editName = document.getElementById('edit-name');
            const editGuard = document.getElementById('edit-guard');

            document.querySelectorAll('[data-edit-permission]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    editForm.action = btn.getAttribute('data-update-url');
                    var idMatch = /\/permissions\/(\d+)/.exec(btn.getAttribute('data-update-url'));
                    editIdInput.value = idMatch ? idMatch[1] : '';
                    editName.value = btn.getAttribute('data-name') || '';
                    editGuard.value = btn.getAttribute('data-guard') || 'web';
                    editDialog.showModal();
                });
            });

            @if ($errors->any() && old('_permission_id'))
                editDialog.showModal();
            @elseif ($errors->any() && ! old('_permission_id'))
                document.getElementById('create-permission-dialog').showModal();
            @endif
        })();
    </script>
</x-layout>
