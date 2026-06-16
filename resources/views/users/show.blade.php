@php
    $parts = preg_split('/\s+/', trim($user->name ?? ''));
    $initials = strtoupper(
        count($parts) >= 2
            ? mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1)
            : mb_substr($user->email, 0, 1)
    );
    $isSuperAdminUser = $user->hasRole(\App\Enums\RolesEnum::SUPER_ADMIN->value);
    $canManage = ! $isSuperAdminUser;
@endphp

<x-layout title="{{ $user->name }} — {{ config('app.name') }}">
    <div class="mx-auto w-full min-w-0 max-w-4xl">
            <a
                href="{{ route('users.index') }}"
                class="inline-flex items-center gap-1 text-sm font-semibold text-primary transition hover:underline"
            >
                <x-lumina.icon name="arrow_back" class="!text-lg" />
                {{ __('Back to users') }}
            </a>

            <div class="mt-6 rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-6 shadow-sm ring-1 ring-outline-variant/5 sm:p-8">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex min-w-0 gap-4">
                        <div
                            class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary/15 font-headline text-2xl font-extrabold text-primary ring-2 ring-primary/20 sm:h-20 sm:w-20 sm:text-3xl"
                            aria-hidden="true"
                        >
                            {{ $initials }}
                        </div>
                        <div class="min-w-0">
                            <span class="inline-flex rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest text-primary bg-primary/10">
                                {{ __('Team member') }}
                            </span>
                            <h1 class="font-headline mt-2 text-2xl font-extrabold tracking-tight text-on-surface sm:text-3xl">
                                {{ $user->name }}
                            </h1>
                            <p class="mt-1 flex items-center gap-2 text-sm text-on-surface-variant">
                                <x-lumina.icon name="mail" class="!text-base shrink-0 opacity-80" />
                                <span class="truncate">{{ $user->email }}</span>
                            </p>
                            @if ($user->created_at)
                                <p class="mt-2 text-xs text-on-surface-variant">
                                    {{ __('Member since :date', ['date' => $user->created_at->toFormattedDateString()]) }}
                                </p>
                            @endif
                        </div>
                    </div>

                    @if ($canManage)
                        <div class="flex flex-col gap-2 sm:items-end">
                            <div class="flex flex-wrap gap-2">
                                @if (! $user->hasVerifiedEmail())
                                    <form method="post" action="{{ route('users.send-email-verification', $user) }}" class="inline">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-outline-variant/25 bg-surface-container-low px-4 py-2.5 text-sm font-semibold text-on-surface transition hover:bg-surface-container-high active:scale-[0.98]"
                                        >
                                            <x-lumina.icon name="mark_email_read" class="!text-lg" />
                                            {{ __('Send verification') }}
                                        </button>
                                    </form>
                                @endif
                                @if ($user->email_verified_at === null && $user->password === null)
                                    <form method="post" action="{{ route('users.resend-invitation', $user) }}" class="inline">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-on-primary shadow-sm transition hover:bg-primary-dim active:scale-[0.98]"
                                        >
                                            <x-lumina.icon name="forward_to_inbox" class="!text-lg" />
                                            {{ __('Resend invitation') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                            <a
                                href="{{ route('users.edit', $user) }}"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-outline-variant/25 px-4 py-2.5 text-sm font-bold text-primary transition hover:bg-primary/10 active:scale-[0.98]"
                            >
                                <x-lumina.icon name="edit" class="!text-lg" />
                                {{ __('Edit user') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            @if (session('success'))
                <div
                    class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200"
                    role="status"
                >
                    {{ session('success') }}
                </div>
            @endif

            @if (session('info'))
                <div
                    class="mt-6 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-950 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-200"
                    role="status"
                >
                    {{ session('info') }}
                </div>
            @endif

            @if (session('error'))
                <div
                    class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200"
                    role="alert"
                >
                    {{ session('error') }}
                </div>
            @endif

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

            <div class="mt-8 grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-6 shadow-sm ring-1 ring-outline-variant/5">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-primary/10 p-2.5 text-primary">
                            <x-lumina.icon name="badge" class="!text-2xl" />
                        </div>
                        <h2 class="font-headline text-lg font-bold text-on-surface">
                            {{ __('Account') }}
                        </h2>
                    </div>
                    <dl class="mt-5 space-y-4 text-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant/15 pb-4">
                            <dt class="font-medium text-on-surface-variant">{{ __('Email status') }}</dt>
                            <dd>
                                @if ($user->email_verified_at)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-tertiary/15 px-3 py-1 text-xs font-bold text-tertiary">
                                        <x-lumina.icon name="check_circle" class="!text-sm" filled />
                                        {{ __('Verified') }}
                                    </span>
                                    <span class="mt-1 block text-end text-xs text-on-surface-variant sm:text-start">
                                        {{ $user->email_verified_at->toFormattedDateString() }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/15 px-3 py-1 text-xs font-bold text-amber-800 dark:text-amber-200">
                                        <x-lumina.icon name="schedule" class="!text-sm" />
                                        {{ __('Pending') }}
                                    </span>
                                @endif
                            </dd>
                        </div>
                        @if ($user->provider)
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <dt class="font-medium text-on-surface-variant">{{ __('Sign-in provider') }}</dt>
                                <dd class="inline-flex items-center gap-2 rounded-lg bg-surface-container-low px-3 py-1.5 font-semibold capitalize text-on-surface">
                                    <x-lumina.icon name="link" class="!text-base text-on-surface-variant" />
                                    {{ $user->provider }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <div class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-6 shadow-sm ring-1 ring-outline-variant/5">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-tertiary/10 p-2.5 text-tertiary">
                            <x-lumina.icon name="shield_person" class="!text-2xl" />
                        </div>
                        <h2 class="font-headline text-lg font-bold text-on-surface">
                            {{ __('Roles') }}
                        </h2>
                    </div>
                    @if ($user->roles->isEmpty())
                        <p class="mt-5 text-sm text-on-surface-variant">
                            {{ __('No roles assigned.') }}
                        </p>
                    @else
                        <ul class="mt-5 flex flex-wrap gap-2">
                            @foreach ($user->roles as $role)
                                <li
                                    class="inline-flex items-center gap-1.5 rounded-full bg-surface-container-high px-4 py-1.5 text-sm font-semibold text-on-surface ring-1 ring-outline-variant/15"
                                >
                                    <x-lumina.icon name="person" class="!text-base text-primary" />
                                    {{ $role->name }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                @if ($user->permissions->isNotEmpty())
                    <div class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-6 shadow-sm ring-1 ring-outline-variant/5 lg:col-span-2">
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl bg-error/10 p-2.5 text-error">
                                <x-lumina.icon name="key" class="!text-2xl" />
                            </div>
                            <div>
                                <h2 class="font-headline text-lg font-bold text-on-surface">
                                    {{ __('Direct permissions') }}
                                </h2>
                                <p class="text-xs text-on-surface-variant">
                                    {{ __('In addition to permissions inherited from roles.') }}
                                </p>
                            </div>
                        </div>
                        <ul class="mt-4 max-h-52 space-y-2 overflow-y-auto rounded-xl bg-surface-container-low p-4 font-mono text-xs text-on-surface-variant">
                            @foreach ($user->permissions as $permission)
                                <li class="flex items-center gap-2 border-b border-outline-variant/10 pb-2 last:border-0 last:pb-0">
                                    <x-lumina.icon name="chevron_right" class="!text-sm text-primary opacity-70" />
                                    {{ $permission->name }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
    </div>
</x-layout>
