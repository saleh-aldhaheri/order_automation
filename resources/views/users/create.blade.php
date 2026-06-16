<x-layout title="{{ __('Invite user') }} — {{ config('app.name') }}">
    <div class="mx-auto w-full min-w-0 max-w-xl">
            <a
                href="{{ route('users.index') }}"
                class="inline-flex items-center gap-1 text-sm font-semibold text-primary transition hover:underline"
            >
                <x-lumina.icon name="arrow_back" class="!text-lg" />
                {{ __('Back to users') }}
            </a>

            <div class="mt-6 flex gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary text-on-primary shadow-sm">
                    <x-lumina.icon name="person_add" class="!text-3xl" />
                </div>
                <div>
                    <span class="inline-flex rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest text-primary bg-primary/10">
                        {{ __('Invite') }}
                    </span>
                    <h1 class="font-headline mt-2 text-2xl font-extrabold tracking-tight text-on-surface sm:text-3xl">
                        {{ __('Invite user') }}
                    </h1>
                    <p class="mt-1 text-sm text-on-surface-variant">
                        {{ __('We’ll email them a link to set their password.') }}
                    </p>
                </div>
            </div>

            <div
                class="mt-6 flex gap-3 rounded-2xl border border-outline-variant/15 bg-primary/5 p-4 text-sm text-on-surface ring-1 ring-primary/10"
                role="note"
            >
                <x-lumina.icon name="mail" class="!text-2xl shrink-0 text-primary" />
                <p class="leading-relaxed text-on-surface-variant">
                    {{ __('They must use the link in the email before signing in. You can resend the invitation from their profile if needed.') }}
                </p>
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

            <form method="post" action="{{ route('users.store') }}" class="mt-8 space-y-6">
                @csrf

                <div class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-6 shadow-sm ring-1 ring-outline-variant/5 sm:p-8">
                    <h2 class="font-headline text-sm font-bold text-on-surface">
                        {{ __('Details') }}
                    </h2>
                    <div class="mt-5 space-y-5">
                        <div>
                            <label for="name" class="mb-1.5 block text-sm font-medium text-on-surface">
                                {{ __('Name') }}
                            </label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name') }}"
                                required
                                autocomplete="name"
                                class="w-full rounded-xl border-none bg-surface-container-low px-4 py-2.5 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none transition placeholder:text-on-surface-variant/60 focus:ring-2 focus:ring-primary/25"
                            />
                        </div>
                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-medium text-on-surface">
                                {{ __('Email') }}
                            </label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autocomplete="email"
                                class="w-full rounded-xl border-none bg-surface-container-low px-4 py-2.5 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none transition placeholder:text-on-surface-variant/60 focus:ring-2 focus:ring-primary/25"
                            />
                        </div>
                        <div>
                            <label for="role" class="mb-1.5 block text-sm font-medium text-on-surface">
                                {{ __('Role') }}
                            </label>
                            <select
                                id="role"
                                name="role"
                                required
                                class="w-full rounded-xl border-none bg-surface-container-low px-4 py-2.5 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none focus:ring-2 focus:ring-primary/25"
                            >
                                <option value="" disabled @selected(old('role') === null)>{{ __('Choose a role…') }}</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected(old('role') == $role->id)>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($roles->isEmpty())
                                <p class="mt-2 flex items-start gap-2 text-xs font-medium text-amber-800 dark:text-amber-200">
                                    <x-lumina.icon name="warning" class="!text-base shrink-0" />
                                    {{ __('No assignable roles exist yet. Create a role first.') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button
                        type="submit"
                        class="font-headline inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-bold text-on-primary shadow-sm transition hover:bg-primary-dim active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                        @disabled($roles->isEmpty())
                    >
                        <x-lumina.icon name="send" class="!text-lg" />
                        {{ __('Send invitation') }}
                    </button>
                    <a
                        href="{{ route('users.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-outline-variant/25 bg-surface-container-low px-6 py-3 text-sm font-semibold text-on-surface transition hover:bg-surface-container-high active:scale-[0.98]"
                    >
                        {{ __('Cancel') }}
                    </a>
                </div>
            </form>
    </div>
</x-layout>
