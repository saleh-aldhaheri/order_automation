@php
    $p = preg_split('/\s+/', trim($user->name ?? ''));
    $heroInitials = strtoupper(
        count($p) >= 2
            ? mb_substr($p[0], 0, 1) . mb_substr($p[1], 0, 1)
            : mb_substr((string) $user->email, 0, 1)
    );
@endphp

<x-layout title="{{ __('Profile') }} — {{ config('app.name') }}">
    <div class="mx-auto w-full min-w-0 max-w-4xl">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ url('/') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline">
                    <x-lumina.icon name="arrow_back" class="!text-base" />
                    {{ __('Back') }}
                </a>
                <h1 class="font-headline mt-4 text-3xl font-bold tracking-tight text-on-surface">
                    {{ __('Your profile') }}
                </h1>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-on-surface-variant">
                    {{ __('Update how you appear in the app and keep your sign-in credentials current.') }}
                </p>
            </div>
        </div>

        @if (session('success'))
            <div
                class="mb-8 flex items-start gap-3 rounded-xl border border-emerald-200/80 bg-emerald-50 px-4 py-3 text-sm text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-100"
                role="status"
            >
                <x-lumina.icon name="check_circle" class="!text-xl shrink-0 text-emerald-600 dark:text-emerald-400" />
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div
                class="mb-8 rounded-xl border border-red-200/80 bg-red-50 px-4 py-3 text-sm text-red-950 dark:border-red-900 dark:bg-red-950/50 dark:text-red-100"
                role="alert"
            >
                <p class="mb-2 flex items-center gap-2 font-semibold">
                    <x-lumina.icon name="error" class="!text-xl" />
                    {{ __('Please fix the following:') }}
                </p>
                <ul class="list-inside list-disc space-y-1 pl-1 text-red-900 dark:text-red-200">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-8 lg:grid-cols-5 lg:gap-10">
            <div class="space-y-8 lg:col-span-3">
                <section
                    class="overflow-hidden rounded-2xl border border-outline-variant/15 bg-surface-container-lowest shadow-sm ring-1 ring-outline-variant/5"
                >
                    <div class="relative bg-gradient-to-br from-primary/15 via-surface-container-low to-surface-container-lowest px-6 py-8 sm:px-8">
                        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(43,74,220,0.12),transparent_55%)] dark:bg-[radial-gradient(ellipse_at_top_right,rgba(129,153,255,0.08),transparent_55%)]" aria-hidden="true"></div>
                        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center">
                            <div
                                class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-primary font-headline text-2xl font-bold text-on-primary shadow-lg shadow-primary/25 ring-4 ring-surface-container-lowest"
                                aria-hidden="true"
                            >
                                {{ $heroInitials }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <h2 class="font-headline text-xl font-bold text-on-surface">{{ $user->name }}</h2>
                                <p class="mt-0.5 truncate text-sm text-on-surface-variant">{{ $user->email }}</p>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    @if ($user->hasVerifiedEmail())
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:text-emerald-300"
                                        >
                                            <x-lumina.icon name="verified" class="!text-sm" :filled="true" />
                                            {{ __('Email verified') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/15 px-2.5 py-0.5 text-xs font-semibold text-amber-900 dark:text-amber-200">
                                            <x-lumina.icon name="schedule" class="!text-sm" />
                                            {{ __('Email not verified') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="post" action="{{ route('profile.update') }}" class="space-y-0">
                        @csrf
                        @method('PUT')

                        <div class="border-t border-outline-variant/10 px-6 py-6 sm:px-8">
                            <div class="mb-4 flex items-center gap-2">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-surface-container-high text-primary">
                                    <x-lumina.icon name="badge" class="!text-xl" />
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-on-surface">{{ __('Account details') }}</h3>
                                    <p class="text-xs text-on-surface-variant">{{ __('Name and email are shown to administrators.') }}</p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label for="name" class="mb-1.5 block text-sm font-medium text-on-surface">{{ __('Name') }}</label>
                                    <input
                                        id="name"
                                        name="name"
                                        type="text"
                                        value="{{ old('name', $user->name) }}"
                                        required
                                        autocomplete="name"
                                        class="w-full rounded-xl border-none bg-surface-container-low px-3.5 py-2.5 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none transition focus:ring-2 focus:ring-primary/30"
                                    />
                                </div>
                                <div>
                                    <label for="email" class="mb-1.5 block text-sm font-medium text-on-surface">{{ __('Email') }}</label>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        value="{{ old('email', $user->email) }}"
                                        required
                                        autocomplete="email"
                                        class="w-full rounded-xl border-none bg-surface-container-low px-3.5 py-2.5 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none transition focus:ring-2 focus:ring-primary/30"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-outline-variant/10 px-6 py-6 sm:px-8">
                            <div class="mb-4 flex items-center gap-2">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-surface-container-high text-primary">
                                    <x-lumina.icon name="lock" class="!text-xl" />
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-on-surface">{{ __('Password & security') }}</h3>
                                    <p class="text-xs text-on-surface-variant">{{ __('Leave password fields empty to keep your current password.') }}</p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label for="current_password" class="mb-1.5 block text-sm font-medium text-on-surface">{{ __('Current password') }}</label>
                                    <input
                                        id="current_password"
                                        name="current_password"
                                        type="password"
                                        autocomplete="current-password"
                                        class="w-full rounded-xl border-none bg-surface-container-low px-3.5 py-2.5 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none transition focus:ring-2 focus:ring-primary/30"
                                    />
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-1">
                                        <label for="password" class="mb-1.5 block text-sm font-medium text-on-surface">{{ __('New password') }}</label>
                                        <input
                                            id="password"
                                            name="password"
                                            type="password"
                                            autocomplete="new-password"
                                            class="w-full rounded-xl border-none bg-surface-container-low px-3.5 py-2.5 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none transition focus:ring-2 focus:ring-primary/30"
                                        />
                                    </div>
                                    <div class="sm:col-span-1">
                                        <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-on-surface">{{ __('Confirm new password') }}</label>
                                        <input
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            type="password"
                                            autocomplete="new-password"
                                            class="w-full rounded-xl border-none bg-surface-container-low px-3.5 py-2.5 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none transition focus:ring-2 focus:ring-primary/30"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-outline-variant/10 bg-surface-container-low/40 px-6 py-4 sm:px-8">
                            <button
                                type="submit"
                                class="font-headline inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-bold text-on-primary shadow-md shadow-primary/20 transition hover:opacity-95 active:scale-[0.98]"
                            >
                                <x-lumina.icon name="save" class="!text-lg" />
                                {{ __('Save changes') }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <aside class="lg:col-span-2">
                <div class="sticky top-8 space-y-4">
                    <div class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-5 shadow-sm ring-1 ring-outline-variant/5">
                        <h3 class="text-sm font-bold text-on-surface">{{ __('Tips') }}</h3>
                        <ul class="mt-3 space-y-3 text-sm text-on-surface-variant">
                            <li class="flex gap-2">
                                <x-lumina.icon name="info" class="!text-lg shrink-0 text-primary" />
                                <span>{{ __('Use a unique password you do not reuse on other sites.') }}</span>
                            </li>
                            <li class="flex gap-2">
                                <x-lumina.icon name="alternate_email" class="!text-lg shrink-0 text-primary" />
                                <span>{{ __('Changing your email may require verification again, depending on your setup.') }}</span>
                            </li>
                        </ul>
                    </div>
                    <p class="text-center text-xs text-on-surface-variant lg:text-left">
                        {{ __('Need to manage roles or invitations? Ask a super admin or open the Users section from the sidebar.') }}
                    </p>
                </div>
            </aside>
        </div>
    </div>
</x-layout>
