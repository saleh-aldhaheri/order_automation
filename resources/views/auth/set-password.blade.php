@php
    $inputBase =
        'w-full rounded-lg border border-app-border bg-app-bg px-3 py-2 text-sm text-app-ink shadow-inner outline-none transition focus:border-app-accent focus:ring-2 focus:ring-app-accent/25 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:border-red-500 dark:focus:ring-red-500/25';
@endphp

<x-layout :title="'Set password — ' . config('app.name', 'Dashboard')">
    <div class="flex flex-1 items-center justify-center px-4 py-10 sm:py-16">
        <div
            class="w-full max-w-md rounded-xl border border-app-border bg-app-surface p-6 shadow-md dark:border-zinc-800 dark:bg-zinc-900 sm:p-8"
        >
            <div class="mb-8 text-center sm:text-left">
                <div
                    class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-app-accent/10 text-app-accent dark:bg-red-500/15 dark:text-red-400 sm:mx-0"
                    aria-hidden="true"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H3.75v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"
                        />
                    </svg>
                </div>
                <h1 class="text-xl font-semibold tracking-tight text-app-ink dark:text-zinc-50 sm:text-2xl">
                    Choose your password
                </h1>
                <p class="mt-2 text-sm text-app-muted dark:text-zinc-400">
                    Create a password for
                    <span class="font-medium text-app-ink dark:text-zinc-200">{{ $user->email }}</span>
                    . This link expires after use or when it reaches its time limit.
                </p>
            </div>

            @if (session('status'))
                <div
                    class="mb-6 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200"
                    role="status"
                >
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div
                    class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
                    role="alert"
                    aria-labelledby="set-password-errors-heading"
                >
                    <p id="set-password-errors-heading" class="font-semibold">
                        Could not save your password
                    </p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->unique() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Post must use the exact query string from the signed GET URL (rebuilding with http_build_query reordered id/expires and broke the HMAC). --}}
            <form
                method="POST"
                action="{{ route('auth.set-password.update').(request()->getQueryString() ? '?'.request()->getQueryString() : '') }}"
                class="flex flex-col gap-5"
            >
                @csrf

                <div class="flex flex-col gap-2">
                    <label for="password" class="text-sm font-medium text-app-ink dark:text-zinc-200">
                        New password
                    </label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="{{ $inputBase }} @error('password') border-red-500 ring-2 ring-red-200 dark:border-red-500 dark:ring-red-900/50 @enderror"
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                    >
                    @error('password')
                        <p id="password-error" class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @else
                        <p class="text-xs text-app-muted dark:text-zinc-500">
                            At least 8 characters, with upper &amp; lowercase letters and a number.
                        </p>
                    @enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="password_confirmation" class="text-sm font-medium text-app-ink dark:text-zinc-200">
                        Confirm password
                    </label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="{{ $inputBase }} @error('password_confirmation') border-red-500 ring-2 ring-red-200 dark:border-red-500 dark:ring-red-900/50 @enderror"
                        @error('password_confirmation') aria-invalid="true" @enderror
                    >
                    @error('password_confirmation')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="mt-1 w-full rounded-lg bg-app-accent py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-app-accent focus:ring-offset-2 dark:focus:ring-offset-zinc-900"
                >
                    Save password
                </button>
            </form>

            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-app-border dark:border-zinc-700"></div>
                </div>
                <div class="relative flex justify-center text-xs font-medium uppercase tracking-wide">
                    <span class="bg-app-surface px-3 text-app-muted dark:bg-zinc-900 dark:text-zinc-500">
                        Or continue with
                    </span>
                </div>
            </div>

            <a
                href="{{ route('auth.social.redirect', ['provider' => 'google']) }}"
                class="group flex w-full items-center justify-center gap-3 rounded-lg border border-zinc-200 bg-white py-2.5 text-sm font-medium text-zinc-700 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50 hover:shadow focus:outline-none focus:ring-2 focus:ring-zinc-400/30 focus:ring-offset-2 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-200 dark:hover:border-zinc-500 dark:hover:bg-zinc-800/80 dark:focus:ring-zinc-500/30 dark:focus:ring-offset-zinc-900"
            >
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        fill="#4285F4"
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                    />
                    <path
                        fill="#34A853"
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                    />
                    <path
                        fill="#FBBC05"
                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                    />
                    <path
                        fill="#EA4335"
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                    />
                </svg>
                <span>Sign in with Google</span>
            </a>

            <p class="mt-8 text-center text-sm text-app-muted dark:text-zinc-500 sm:text-left">
                Wrong place?
                <a
                    href="{{ route('auth.login') }}"
                    class="font-medium text-app-accent underline-offset-2 hover:underline dark:text-red-400"
                >
                    Back to sign in
                </a>
            </p>
        </div>
    </div>
</x-layout>
