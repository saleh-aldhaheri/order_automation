<x-layout title="{{ __('Edit role') }} — {{ config('app.name') }}">
    <div class="mx-auto w-full min-w-0 max-w-4xl">
            <a
                href="{{ route('roles.index') }}"
                class="inline-flex items-center gap-1 text-sm font-semibold text-primary transition hover:underline"
            >
                <x-lumina.icon name="arrow_back" class="!text-lg" />
                {{ __('Back to roles') }}
            </a>

            <div class="mt-6 flex gap-4">
                <div
                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary/15 text-primary ring-2 ring-primary/20"
                    aria-hidden="true"
                >
                    <x-lumina.icon name="shield_person" class="!text-3xl" />
                </div>
                <div class="min-w-0">
                    <span
                        class="inline-flex rounded-lg px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest text-primary bg-primary/10"
                    >
                        {{ __('Edit') }}
                    </span>
                    <h1 class="font-headline mt-2 text-2xl font-extrabold tracking-tight text-on-surface sm:text-3xl">
                        {{ __('Edit role') }}
                    </h1>
                    <p class="mt-1 text-sm text-on-surface-variant">
                        {{ __('Update :name and its permissions.', ['name' => $role->name]) }}
                    </p>
                </div>
            </div>

            @if ($errors->any())
                <div
                    class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200"
                    role="alert"
                >
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="post" action="{{ route('roles.update', $role) }}" class="mt-8 space-y-6">
                @csrf
                @method('PUT')

                <div class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-6 shadow-sm ring-1 ring-outline-variant/5 sm:p-8">
                    <div class="flex flex-wrap items-start gap-3">
                        <div class="rounded-xl bg-surface-container-high p-2 text-primary">
                            <x-lumina.icon name="badge" class="!text-2xl" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 class="font-headline text-sm font-bold text-on-surface">{{ __('Role identity') }}</h2>
                            <p class="mt-0.5 text-xs text-on-surface-variant">
                                {{ __('Changing the name updates the role key for new assignments.') }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-6">
                        <label for="role-name" class="mb-1.5 block text-sm font-medium text-on-surface">
                            {{ __('Role name') }}
                        </label>
                        <input
                            id="role-name"
                            name="name"
                            type="text"
                            value="{{ old('name', $role->name) }}"
                            required
                            minlength="2"
                            maxlength="255"
                            autocomplete="off"
                            class="w-full max-w-xl rounded-xl border-none bg-surface-container-low px-4 py-2.5 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none transition focus:ring-2 focus:ring-primary/25"
                        />
                    </div>
                </div>

                <div class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-6 shadow-sm ring-1 ring-outline-variant/5 sm:p-8">
                    <div class="flex flex-wrap items-start gap-3">
                        <div class="rounded-xl bg-surface-container-high p-2 text-primary">
                            <x-lumina.icon name="key" class="!text-2xl" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 class="font-headline text-sm font-bold text-on-surface">{{ __('Permissions') }}</h2>
                            <p class="mt-0.5 text-xs text-on-surface-variant">
                                {{ __('Adjust checked permissions for this role. Changes apply on the next request.') }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-6">
                        @include('roles.partials.permission-groups', [
                            'permissionsGrouped' => $permissionsGrouped,
                            'selectedIds' => $selectedIds,
                        ])
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button
                        type="submit"
                        class="font-headline inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-bold text-on-primary shadow-sm transition hover:bg-primary-dim active:scale-[0.98]"
                    >
                        <x-lumina.icon name="save" class="!text-lg" />
                        {{ __('Save changes') }}
                    </button>
                    <a
                        href="{{ route('roles.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-outline-variant/25 bg-surface-container-low px-6 py-3 text-sm font-semibold text-on-surface transition hover:bg-surface-container-high active:scale-[0.98]"
                    >
                        {{ __('Cancel') }}
                    </a>
                </div>
            </form>
    </div>
</x-layout>
