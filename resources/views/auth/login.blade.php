<x-layout :title="'Sign in — ' . config('app.name', 'Dashboard')">
    <div class="flex flex-1 items-center justify-center px-4 py-10 sm:py-16">
        <div
            class="w-full max-w-md rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-6 shadow-lg ring-1 ring-outline-variant/5 sm:p-8"
        >
            <div class="mb-8 text-center sm:text-left">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-on-primary sm:mx-0">
                    <x-lumina.icon name="lock" class="!text-2xl text-on-primary" />
                </div>
                <h1 class="font-headline text-2xl font-extrabold tracking-tight text-on-surface">
                    {{ __('Sign in') }}
                </h1>
                <p class="mt-2 text-sm text-on-surface-variant">
                    {{ __('Enter your credentials to continue.') }}
                </p>
            </div>

            @if (session('status'))
                <div
                    class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200"
                    role="status"
                >
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div
                    class="mb-6 rounded-xl border border-red-200/80 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200"
                    role="alert"
                >
                    {{ session('error') }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('auth.authenticate') }}"
                class="flex flex-col gap-5"
            >
                @csrf

                <div class="flex flex-col gap-2">
                    <label for="email" class="text-sm font-medium text-on-surface">
                        {{ __('Email') }}
                    </label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        class="w-full rounded-full border-none bg-surface-container-low px-4 py-2.5 text-sm text-on-surface outline-none ring-1 ring-outline-variant/20 transition placeholder:text-on-surface-variant/60 focus:ring-2 focus:ring-primary/25 @error('email') ring-2 ring-error @enderror"
                    >
                    @error('email')
                        <p class="text-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="password" class="text-sm font-medium text-on-surface">
                        {{ __('Password') }}
                    </label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="w-full rounded-full border-none bg-surface-container-low px-4 py-2.5 text-sm text-on-surface outline-none ring-1 ring-outline-variant/20 transition placeholder:text-on-surface-variant/60 focus:ring-2 focus:ring-primary/25 @error('password') ring-2 ring-error @enderror"
                    >
                    @error('password')
                        <p class="text-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="font-headline mt-1 w-full rounded-xl bg-primary py-3 text-sm font-bold text-on-primary shadow-sm transition hover:bg-primary-dim active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                >
                    {{ __('Sign in') }}
                </button>
            </form>

            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-outline-variant/20"></div>
                </div>
                <div class="relative flex justify-center text-xs font-semibold uppercase tracking-wide text-on-surface-variant">
                    <span class="bg-surface-container-lowest px-3">{{ __('Or continue with') }}</span>
                </div>
            </div>

            <a
                href="{{ route('auth.social.redirect', ['provider' => 'google']) }}"
                class="group flex w-full items-center justify-center gap-3 rounded-xl border border-outline-variant/20 bg-surface-container-low py-3 text-sm font-semibold text-on-surface transition hover:bg-surface-container-high active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/25"
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
                <span>{{ __('Sign in with Google') }}</span>
            </a>
        </div>
    </div>
</x-layout>
