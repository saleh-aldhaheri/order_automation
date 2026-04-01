<x-layout :title="'Sign in — ' . config('app.name', 'Dashboard')">
    <div class="flex flex-1 items-center justify-center px-4 py-10 sm:py-16">
        <div
            class="w-full max-w-md rounded-xl border border-app-border bg-app-surface p-6 shadow-md dark:border-zinc-800 dark:bg-zinc-900 sm:p-8"
        >
            <div class="mb-8 text-center sm:text-left">
                <h1 class="text-xl font-semibold tracking-tight text-app-ink dark:text-zinc-50 sm:text-2xl">
                    Sign in
                </h1>
                <p class="mt-2 text-sm text-app-muted dark:text-zinc-400">
                    Enter your credentials to continue.
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

            @if (session('error'))
                <div
                    class="mb-6 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
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
                    <label for="email" class="text-sm font-medium text-app-ink dark:text-zinc-200">
                        Email
                    </label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        class="w-full rounded-lg border border-app-border bg-app-bg px-3 py-2 text-sm text-app-ink shadow-inner outline-none transition focus:border-app-accent focus:ring-2 focus:ring-app-accent/25 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:border-red-500 dark:focus:ring-red-500/25 @error('email') border-red-500 ring-2 ring-red-200 dark:border-red-500 dark:ring-red-900/50 @enderror"
                    >
                    @error('email')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label for="password" class="text-sm font-medium text-app-ink dark:text-zinc-200">
                        Password
                    </label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="w-full rounded-lg border border-app-border bg-app-bg px-3 py-2 text-sm text-app-ink shadow-inner outline-none transition focus:border-app-accent focus:ring-2 focus:ring-app-accent/25 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:border-red-500 dark:focus:ring-red-500/25 @error('password') border-red-500 ring-2 ring-red-200 dark:border-red-500 dark:ring-red-900/50 @enderror"
                    >
                    @error('password')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="mt-1 w-full rounded-lg bg-app-accent py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-app-accent focus:ring-offset-2 dark:focus:ring-offset-zinc-900"
                >
                    Sign in
                </button>
            </form>
        </div>
    </div>
</x-layout>
