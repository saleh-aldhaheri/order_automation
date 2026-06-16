<x-layout :title="'Verify email — ' . config('app.name', 'Dashboard')">
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
                            d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"
                        />
                    </svg>
                </div>
                <h1 class="text-xl font-semibold tracking-tight text-app-ink dark:text-zinc-50 sm:text-2xl">
                    Check your email
                </h1>
                <p class="mt-2 text-sm text-app-muted dark:text-zinc-400">
                    We sent a verification link to finish setting up your account. Open the email and tap the button to
                    verify your address.
                </p>
            </div>

            <div
                class="mb-6 rounded-lg border border-app-border bg-app-bg px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950/80"
            >
                <p class="text-xs font-medium uppercase tracking-wide text-app-muted dark:text-zinc-500">
                    Sending to
                </p>
                <p class="mt-1 break-all text-sm font-medium text-app-ink dark:text-zinc-100">
                    {{ auth()->user()->email }}
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

            <div
                class="mb-6 rounded-lg border border-amber-200/80 bg-amber-50/90 px-3 py-2.5 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100"
                role="note"
            >
                <p class="font-medium text-amber-900 dark:text-amber-200">Didn’t get it?</p>
                <p class="mt-1 text-amber-900/90 dark:text-amber-100/90">
                    Check spam or promotions. If you still don’t see it, ask your administrator to resend the invitation.
                </p>
            </div>

            <p class="text-center text-sm text-app-muted dark:text-zinc-500 sm:text-left">
                Wrong account?
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
