<x-layout title="{{ __('External systems') }} — {{ config('app.name') }}">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="font-headline text-2xl font-bold tracking-tight text-on-surface sm:text-3xl">
                {{ __('External systems') }}
            </h1>
            <p class="mt-1 text-sm text-on-surface-variant">
                {{ __('API clients that authenticate with client credentials and Sanctum tokens. Copy secrets and tokens only when shown — they are not stored in plain text.') }}
            </p>
        </div>
        @can(\App\Enums\PermissionsEnum::EXTERNAL_SYSTEM_CREATE->value)
            <button
                type="button"
                onclick="document.getElementById('create-external-system-dialog').showModal()"
                class="font-headline inline-flex items-center justify-center rounded-xl bg-primary px-5 py-3 text-sm font-bold text-on-primary shadow-sm transition hover:bg-primary-dim active:scale-[0.98]"
            >
                {{ __('Add external system') }}
            </button>
        @endcan
    </div>

    @if (session('success'))
        <div
            class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200"
            role="status"
        >
            {{ session('success') }}
        </div>
    @endif
    <x-data-table>
        <x-slot:toolbar>
            <div class="flex min-w-0 flex-1 flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <p class="shrink-0 text-sm text-on-surface-variant">
                    @if ($external_systems->total() > 0)
                        {{ __('Showing') }}
                        <span class="font-semibold text-on-surface">{{ $external_systems->firstItem() }}</span>
                        –
                        <span class="font-semibold text-on-surface">{{ $external_systems->lastItem() }}</span>
                        {{ __('of') }}
                        <span class="font-semibold text-on-surface">{{ $external_systems->total() }}</span>
                    @else
                        {{ __('No records to show.') }}
                    @endif
                </p>
                <x-data-table.toolbar-filters
                    :action="route('external-systems.index')"
                    :search="$search"
                    :per-page="$perPage"
                    :per-page-options="$perPageOptions"
                    :placeholder="__('System name or client ID…')"
                    input-id="external-systems-table-search"
                    class="min-w-0 lg:max-w-2xl"
                />
            </div>
        </x-slot:toolbar>

        <x-data-table.table>
            <x-data-table.thead>
                <tr>
                    <x-data-table.th>{{ __('System') }}</x-data-table.th>
                    <x-data-table.th>{{ __('Client ID') }}</x-data-table.th>
                    <x-data-table.th>{{ __('Status') }}</x-data-table.th>
                    <x-data-table.th align="end">{{ __('Actions') }}</x-data-table.th>
                </tr>
            </x-data-table.thead>
            <x-data-table.tbody>
                @forelse ($external_systems as $system)
                    <x-data-table.row>
                        <x-data-table.td>
                            <div class="font-medium text-on-surface">
                                {{ $system->system_name }}
                            </div>
                        </x-data-table.td>
                        <x-data-table.td>
                            <span class="font-mono text-xs text-on-surface-variant break-all">{{ $system->client_id }}</span>
                        </x-data-table.td>
                        <x-data-table.td>
                            @if ($system->is_active)
                                <span
                                    class="inline-flex rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:text-emerald-200"
                                >
                                    {{ __('Active') }}
                                </span>
                            @else
                                <span
                                    class="inline-flex rounded-full bg-outline-variant/20 px-2.5 py-0.5 text-xs font-semibold text-on-surface-variant"
                                >
                                    {{ __('Inactive') }}
                                </span>
                            @endif
                        </x-data-table.td>
                        <x-data-table.td align="end">
                            <div class="flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                                @can(\App\Enums\PermissionsEnum::EXTERNAL_SYSTEM_UPDATE->value)
                                    <button
                                        type="button"
                                        class="text-sm font-semibold text-primary hover:underline"
                                        data-edit-external-system
                                        data-update-url="{{ route('external-systems.update', $system) }}"
                                        data-system-name="{{ $system->system_name }}"
                                        data-is-active="{{ $system->is_active ? '1' : '0' }}"
                                        data-id="{{ $system->id }}"
                                    >
                                        {{ __('Edit') }}
                                    </button>
                                @endcan
                                @can(\App\Enums\PermissionsEnum::EXTERNAL_SYSTEM_GENERATE_TOKEN->value)
                                    <button
                                        type="button"
                                        class="text-sm font-semibold text-on-surface hover:underline"
                                        data-pending-b64="{{ base64_encode(json_encode([
                                            'url' => route('external-systems.generate-token', $system),
                                            'method' => 'post',
                                            'title' => __('Generate API token?'),
                                            'body' => __('Existing tokens for this system will be revoked. You can copy the new token once on the next screen.'),
                                        ], JSON_THROW_ON_ERROR)) }}"
                                    >
                                        {{ __('Token') }}
                                    </button>
                                @endcan
                                @can(\App\Enums\PermissionsEnum::EXTERNAL_SYSTEM_ROTATE_SECRET->value)
                                    <button
                                        type="button"
                                        class="text-sm font-semibold text-on-surface hover:underline"
                                        data-pending-b64="{{ base64_encode(json_encode([
                                            'url' => route('external-systems.rotate-client-secret', $system),
                                            'method' => 'put',
                                            'title' => __('Rotate client secret?'),
                                            'body' => __('The previous secret stops working immediately. You can copy the new secret once on the next screen.'),
                                        ], JSON_THROW_ON_ERROR)) }}"
                                    >
                                        {{ __('Secret') }}
                                    </button>
                                @endcan
                                @can(\App\Enums\PermissionsEnum::EXTERNAL_SYSTEM_REVOKE_TOKEN->value)
                                    <button
                                        type="button"
                                        class="text-sm font-semibold text-on-surface-variant hover:underline"
                                        data-pending-b64="{{ base64_encode(json_encode([
                                            'url' => route('external-systems.revoke-token', $system),
                                            'method' => 'post',
                                            'title' => __('Revoke tokens?'),
                                            'body' => __('All API tokens for this system will be removed. Clients must authenticate again.'),
                                        ], JSON_THROW_ON_ERROR)) }}"
                                    >
                                        {{ __('Revoke') }}
                                    </button>
                                @endcan
                                @can(\App\Enums\PermissionsEnum::EXTERNAL_SYSTEM_DELETE->value)
                                    <button
                                        type="button"
                                        class="text-sm font-semibold text-error hover:underline"
                                        data-delete-external-system
                                        data-delete-url="{{ route('external-systems.destroy', $system) }}"
                                        data-name="{{ $system->system_name }}"
                                    >
                                        {{ __('Delete') }}
                                    </button>
                                @endcan
                            </div>
                        </x-data-table.td>
                    </x-data-table.row>
                @empty
                    <x-data-table.row>
                        <x-data-table.td colspan="4" empty>
                            @if (($search ?? '') !== '')
                                {{ __('No external systems match your search.') }}
                            @else
                                {{ __('No external systems yet. Add one to issue client credentials.') }}
                            @endif
                        </x-data-table.td>
                    </x-data-table.row>
                @endforelse
            </x-data-table.tbody>
        </x-data-table.table>
    </x-data-table>

    <x-data-table.pagination>
        {{ $external_systems->links() }}
    </x-data-table.pagination>

    @can(\App\Enums\PermissionsEnum::EXTERNAL_SYSTEM_CREATE->value)
        <dialog
            id="create-external-system-dialog"
            class="w-[min(100vw-2rem,28rem)] max-w-lg rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-0 shadow-xl ring-1 ring-outline-variant/5 [&::backdrop]:bg-inverse-surface/50"
        >
            <form method="post" action="{{ route('external-systems.store') }}" class="flex flex-col">
                @csrf
                <div class="border-b border-outline-variant/15 px-5 py-4">
                    <h2 class="font-headline text-lg font-bold text-on-surface">{{ __('New external system') }}</h2>
                    <p class="mt-0.5 text-sm text-on-surface-variant">
                        {{ __('You will receive a one-time client secret to copy after saving.') }}
                    </p>
                </div>
                <div class="space-y-4 px-5 py-4">
                    @if ($errors->any() && ! old('_external_system_id'))
                        <ul class="list-inside list-disc space-y-1 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <div>
                        <label for="create-system-name" class="mb-1.5 block text-sm font-medium text-on-surface">{{ __('System name') }}</label>
                        <input
                            id="create-system-name"
                            name="system_name"
                            value="{{ old('system_name') }}"
                            required
                            minlength="2"
                            maxlength="256"
                            class="w-full rounded-xl border-none bg-surface-container-low px-3 py-2 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none focus:ring-2 focus:ring-primary/25"
                            autocomplete="off"
                        />
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_active" value="0" />
                        <input
                            type="checkbox"
                            name="is_active"
                            id="create-is-active"
                            value="1"
                            class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary/25"
                            @if (old('is_active') !== null)
                                @checked(old('is_active') == 1 || old('is_active') === '1' || old('is_active') === true)
                            @else
                                checked
                            @endif
                        />
                        <label for="create-is-active" class="text-sm font-medium text-on-surface">{{ __('Active') }}</label>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-outline-variant/15 bg-surface-container-low/50 px-5 py-4">
                    <button
                        type="button"
                        onclick="document.getElementById('create-external-system-dialog').close()"
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
    @endcan

    @can(\App\Enums\PermissionsEnum::EXTERNAL_SYSTEM_UPDATE->value)
        <dialog
            id="edit-external-system-dialog"
            class="w-[min(100vw-2rem,28rem)] max-w-lg rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-0 shadow-xl ring-1 ring-outline-variant/5 [&::backdrop]:bg-inverse-surface/50"
        >
            <form
                id="edit-external-system-form"
                method="post"
                action="{{ old('_external_system_id') ? route('external-systems.update', old('_external_system_id')) : '#' }}"
                class="flex flex-col"
            >
                @csrf
                @method('PUT')
                <input type="hidden" name="_external_system_id" id="edit-external-system-id" value="{{ old('_external_system_id') }}" />
                <div class="border-b border-outline-variant/15 px-5 py-4">
                    <h2 class="font-headline text-lg font-bold text-on-surface">{{ __('Edit external system') }}</h2>
                </div>
                <div class="space-y-4 px-5 py-4">
                    @if ($errors->any() && old('_external_system_id'))
                        <ul class="list-inside list-disc space-y-1 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <div>
                        <label for="edit-system-name" class="mb-1.5 block text-sm font-medium text-on-surface">{{ __('System name') }}</label>
                        <input
                            id="edit-system-name"
                            name="system_name"
                            value="{{ old('system_name') }}"
                            required
                            minlength="2"
                            maxlength="256"
                            class="w-full rounded-xl border-none bg-surface-container-low px-3 py-2 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none focus:ring-2 focus:ring-primary/25"
                        />
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_active" value="0" />
                        <input
                            type="checkbox"
                            name="is_active"
                            id="edit-is-active"
                            value="1"
                            class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary/25"
                        />
                        <label for="edit-is-active" class="text-sm font-medium text-on-surface">{{ __('Active') }}</label>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-outline-variant/15 bg-surface-container-low/50 px-5 py-4">
                    <button
                        type="button"
                        onclick="document.getElementById('edit-external-system-dialog').close()"
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
    @endcan

    <dialog
        id="pending-action-dialog"
        class="w-[min(100vw-2rem,26rem)] max-w-md rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-0 shadow-xl ring-1 ring-outline-variant/5 [&::backdrop]:bg-inverse-surface/50"
    >
        <form id="pending-action-form" method="post" action="#" class="flex flex-col">
            @csrf
            <div id="pending-action-method-spoof"></div>
            <div class="border-b border-outline-variant/15 px-5 py-4">
                <h2 class="font-headline text-lg font-bold text-on-surface" id="pending-action-title"></h2>
                <p class="mt-2 text-sm text-on-surface-variant" id="pending-action-body"></p>
            </div>
            <div class="flex justify-end gap-2 border-t border-outline-variant/15 bg-surface-container-low/50 px-5 py-4">
                <button
                    type="button"
                    onclick="document.getElementById('pending-action-dialog').close()"
                    class="rounded-xl border border-outline-variant/20 bg-surface-container-lowest px-4 py-2 text-sm font-semibold text-on-surface transition hover:bg-surface-container-high"
                >
                    {{ __('Cancel') }}
                </button>
                <button
                    type="submit"
                    class="font-headline rounded-xl bg-primary px-4 py-2 text-sm font-bold text-on-primary transition hover:bg-primary-dim"
                >
                    {{ __('Confirm') }}
                </button>
            </div>
        </form>
    </dialog>

    @can(\App\Enums\PermissionsEnum::EXTERNAL_SYSTEM_DELETE->value)
        <dialog
            id="delete-external-system-dialog"
            class="w-[min(100vw-2rem,24rem)] max-w-md rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-0 shadow-xl ring-1 ring-outline-variant/5 [&::backdrop]:bg-inverse-surface/50"
        >
            <form id="delete-external-system-form" method="post" action="#" class="flex flex-col">
                @csrf
                @method('DELETE')
                <div class="border-b border-outline-variant/15 px-5 py-4">
                    <h2 class="font-headline text-lg font-bold text-on-surface">{{ __('Delete external system') }}</h2>
                    <p class="mt-2 text-sm text-on-surface-variant" id="delete-external-system-message"></p>
                </div>
                <div class="flex justify-end gap-2 border-t border-outline-variant/15 bg-surface-container-low/50 px-5 py-4">
                    <button
                        type="button"
                        onclick="document.getElementById('delete-external-system-dialog').close()"
                        class="rounded-xl border border-outline-variant/20 bg-surface-container-lowest px-4 py-2 text-sm font-semibold text-on-surface transition hover:bg-surface-container-high"
                    >
                        {{ __('Cancel') }}
                    </button>
                    <button
                        type="submit"
                        class="font-headline rounded-xl bg-error px-4 py-2 text-sm font-bold text-white transition hover:opacity-90"
                    >
                        {{ __('Delete') }}
                    </button>
                </div>
            </form>
        </dialog>
    @endcan

    <dialog
        id="copy-credential-dialog"
        class="w-[min(100vw-2rem,32rem)] max-w-xl rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-0 shadow-xl ring-1 ring-outline-variant/5 [&::backdrop]:bg-inverse-surface/50"
    >
        <div class="border-b border-outline-variant/15 px-5 py-4">
            <h2 class="font-headline text-lg font-bold text-on-surface" id="copy-credential-heading">{{ __('Copy credential') }}</h2>
            <p class="mt-1 text-sm text-on-surface-variant" id="copy-credential-hint"></p>
        </div>
        <div class="space-y-4 px-5 py-4">
            <div>
                <label for="copy-credential-value" class="mb-1.5 block text-sm font-medium text-on-surface">{{ __('Value') }}</label>
                <textarea
                    id="copy-credential-value"
                    readonly
                    rows="4"
                    class="w-full resize-y rounded-xl border-none bg-surface-container-low px-3 py-2 font-mono text-xs text-on-surface ring-1 ring-outline-variant/20 outline-none focus:ring-2 focus:ring-primary/25"
                ></textarea>
            </div>
            <p class="text-xs text-on-surface-variant">{{ __('Anyone with this value can access the API. Store it in a secret manager — do not commit it to source control.') }}</p>
        </div>
        <div class="flex flex-wrap justify-end gap-2 border-t border-outline-variant/15 bg-surface-container-low/50 px-5 py-4">
            <button
                type="button"
                id="copy-credential-copy-btn"
                class="font-headline inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-bold text-on-primary transition hover:bg-primary-dim"
            >
                <span class="material-symbols-outlined text-lg">content_copy</span>
                {{ __('Copy to clipboard') }}
            </button>
            <button
                type="button"
                onclick="document.getElementById('copy-credential-dialog').close()"
                class="rounded-xl border border-outline-variant/20 bg-surface-container-lowest px-4 py-2 text-sm font-semibold text-on-surface transition hover:bg-surface-container-high"
            >
                {{ __('Close') }}
            </button>
        </div>
    </dialog>

    <script>
        (function () {
            const copyDialog = document.getElementById('copy-credential-dialog');
            const copyHeading = document.getElementById('copy-credential-heading');
            const copyHint = document.getElementById('copy-credential-hint');
            const copyValue = document.getElementById('copy-credential-value');
            const copyBtn = document.getElementById('copy-credential-copy-btn');

            function openCopyDialog(title, hint, value) {
                if (!copyDialog || !copyValue) return;
                copyHeading.textContent = title;
                copyHint.textContent = hint;
                copyValue.value = value;
                copyDialog.showModal();
                requestAnimationFrame(function () {
                    copyValue.focus();
                    copyValue.select();
                });
            }

            if (copyBtn && copyValue) {
                copyBtn.addEventListener('click', function () {
                    copyValue.select();
                    copyValue.setSelectionRange(0, copyValue.value.length);
                    var origHtml = copyBtn.innerHTML;
                    navigator.clipboard.writeText(copyValue.value).then(function () {
                        copyBtn.textContent = @json(__('Copied!'));
                        setTimeout(function () {
                            copyBtn.innerHTML = origHtml;
                        }, 1600);
                    });
                });
            }

            @if (session()->has('copy_token'))
                openCopyDialog(
                    @json(__('API token')),
                    @json(__('This token will not be shown again. Copy it now.')),
                    @json(session('copy_token'))
                );
            @elseif (session()->has('copy_client_secret'))
                openCopyDialog(
                    @json(__('Client secret')),
                    @json(__('This secret will not be shown again. Copy it now.')),
                    @json(session('copy_client_secret'))
                );
            @endif

            const pendingDialog = document.getElementById('pending-action-dialog');
            const pendingForm = document.getElementById('pending-action-form');
            const pendingMethodSpoof = document.getElementById('pending-action-method-spoof');
            const pendingTitle = document.getElementById('pending-action-title');
            const pendingBody = document.getElementById('pending-action-body');

            document.querySelectorAll('[data-pending-b64]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!pendingDialog || !pendingForm || !pendingMethodSpoof || !pendingTitle || !pendingBody) return;
                    var raw = btn.getAttribute('data-pending-b64');
                    if (!raw) return;
                    var payload = JSON.parse(atob(raw));
                    pendingForm.action = payload.url;
                    pendingTitle.textContent = payload.title || '';
                    pendingBody.textContent = payload.body || '';
                    if ((payload.method || 'post').toLowerCase() === 'put') {
                        pendingMethodSpoof.innerHTML =
                            '<input type="hidden" name="_method" value="PUT" autocomplete="off" />';
                    } else {
                        pendingMethodSpoof.innerHTML = '';
                    }
                    pendingDialog.showModal();
                });
            });

            const editDialog = document.getElementById('edit-external-system-dialog');
            const editForm = document.getElementById('edit-external-system-form');
            const editIdInput = document.getElementById('edit-external-system-id');
            const editName = document.getElementById('edit-system-name');
            const editActive = document.getElementById('edit-is-active');

            document.querySelectorAll('[data-edit-external-system]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!editForm || !editIdInput || !editName || !editActive) return;
                    editForm.action = btn.getAttribute('data-update-url');
                    editIdInput.value = btn.getAttribute('data-id') || '';
                    editName.value = btn.getAttribute('data-system-name') || '';
                    editActive.checked = btn.getAttribute('data-is-active') === '1';
                    editDialog.showModal();
                });
            });

            @if ($errors->any() && old('_external_system_id'))
                if (editDialog) editDialog.showModal();
            @elseif ($errors->any() && ! old('_external_system_id'))
                var c = document.getElementById('create-external-system-dialog');
                if (c) c.showModal();
            @endif

            const deleteDialog = document.getElementById('delete-external-system-dialog');
            const deleteForm = document.getElementById('delete-external-system-form');
            const deleteMessage = document.getElementById('delete-external-system-message');

            document.querySelectorAll('[data-delete-external-system]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!deleteForm || !deleteMessage || !deleteDialog) return;
                    deleteForm.action = btn.getAttribute('data-delete-url');
                    var name = btn.getAttribute('data-name') || '';
                    deleteMessage.textContent =
                        @json(__('Are you sure you want to delete :name? This cannot be undone.', ['name' => 'PLACEHOLDER'])).replace(
                            'PLACEHOLDER',
                            name
                        );
                    deleteDialog.showModal();
                });
            });
        })();
    </script>
</x-layout>
