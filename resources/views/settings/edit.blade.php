<x-layout title="{{ __('Site settings') }} — {{ config('app.name') }}">
    <div class="mb-8">
            <a
                href="{{ url('/') }}"
                class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline"
            >
                <x-lumina.icon name="arrow_back" class="!text-lg" />
                {{ __('Back') }}
            </a>
            <h1 class="font-headline mt-4 text-2xl font-bold tracking-tight text-on-surface sm:text-3xl">
                {{ __('Site settings') }}
            </h1>
            <p class="mt-2 max-w-2xl text-sm text-on-surface-variant">
                {{ __('Theme, colors, and layout apply across the app.') }}
            </p>
        </div>

        @if (session('success'))
            <div
                class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200"
                role="status"
            >
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('settings'))
            <div
                class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200"
                role="alert"
            >
                {{ $errors->first('settings') }}
            </div>
        @endif

        <div class="grid gap-8 lg:grid-cols-12 lg:gap-10">
            <form
                method="post"
                action="{{ route('settings.update') }}"
                class="min-w-0 space-y-6 lg:col-span-8"
                enctype="multipart/form-data"
            >
                @csrf
                @method('PUT')

                <div class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-6 shadow-sm ring-1 ring-outline-variant/5 sm:p-8">
                    <h2 class="font-headline text-sm font-bold text-on-surface">
                        {{ __('General') }}
                    </h2>
                    <div class="mt-5 space-y-5">
                        <div>
                            <label for="site_name" class="mb-1.5 block text-sm font-medium text-on-surface">
                                {{ __('Site name') }}
                            </label>
                            <input
                                id="site_name"
                                name="settings[site_name]"
                                type="text"
                                value="{{ old('settings.site_name', $settings['site_name']) }}"
                                data-setting-preview="site_name"
                                class="w-full max-w-xl rounded-xl border-none bg-surface-container-low px-4 py-2.5 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none transition focus:ring-2 focus:ring-primary/25"
                            />
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2 sm:gap-6">
                            <div class="min-w-0">
                                <label for="theme_mode" class="mb-1.5 block text-sm font-medium text-on-surface">
                                    {{ __('Theme mode') }}
                                </label>
                                <select
                                    id="theme_mode"
                                    name="settings[theme_mode]"
                                    class="w-full rounded-xl border-none bg-surface-container-low px-4 py-2.5 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none focus:ring-2 focus:ring-primary/25"
                                >
                                    @foreach (['light', 'dark', 'system'] as $mode)
                                        <option value="{{ $mode }}" @selected(old('settings.theme_mode', $settings['theme_mode']) === $mode)>
                                            {{ __(ucfirst($mode)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="min-w-0">
                                <label for="font_size" class="mb-1.5 block text-sm font-medium text-on-surface">
                                    {{ __('Base font size') }}
                                </label>
                                <select
                                    id="font_size"
                                    name="settings[font_size]"
                                    data-setting-preview="font_size"
                                    class="w-full rounded-xl border-none bg-surface-container-low px-4 py-2.5 text-sm text-on-surface ring-1 ring-outline-variant/20 outline-none focus:ring-2 focus:ring-primary/25"
                                >
                                    @foreach (['sm' => __('Small'), 'md' => __('Medium'), 'lg' => __('Large')] as $val => $label)
                                        <option value="{{ $val }}" @selected(old('settings.font_size', $settings['font_size']) === $val)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-6 shadow-sm ring-1 ring-outline-variant/5 sm:p-8">
                    <h2 class="font-headline text-sm font-bold text-on-surface">{{ __('Brand colors') }}</h2>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2 sm:gap-6">
                        <div class="min-w-0">
                            <label for="primary_color" class="mb-1.5 block text-sm font-medium text-on-surface">
                                {{ __('Primary') }}
                            </label>
                            <input
                                id="primary_color"
                                name="settings[primary_color]"
                                type="color"
                                value="{{ old('settings.primary_color', $settings['primary_color']) }}"
                                data-setting-preview="primary_color"
                                class="h-12 w-full max-w-full cursor-pointer rounded-xl border border-outline-variant/20 bg-surface-container-low"
                            />
                        </div>
                        <div class="min-w-0">
                            <label for="secondary_color" class="mb-1.5 block text-sm font-medium text-on-surface">
                                {{ __('Secondary (text)') }}
                            </label>
                            <input
                                id="secondary_color"
                                name="settings[secondary_color]"
                                type="color"
                                value="{{ old('settings.secondary_color', $settings['secondary_color']) }}"
                                data-setting-preview="secondary_color"
                                class="h-12 w-full max-w-full cursor-pointer rounded-xl border border-outline-variant/20 bg-surface-container-low"
                            />
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-6 shadow-sm ring-1 ring-outline-variant/5 sm:p-8">
                    <h2 class="font-headline text-sm font-bold text-on-surface">{{ __('Layout') }}</h2>
                    <div class="mt-5 space-y-3">
                        <input type="hidden" name="settings[navbar_enabled]" value="1" />
                        <label class="flex max-w-xl items-start gap-3 text-sm text-on-surface">
                            <input type="hidden" name="settings[sidebar_enabled]" value="0" />
                            <input
                                type="checkbox"
                                name="settings[sidebar_enabled]"
                                value="1"
                                class="mt-0.5 rounded border-outline-variant text-primary focus:ring-primary/30"
                                @checked((bool) old('settings.sidebar_enabled', $settings['sidebar_enabled']))
                            />
                            <span>
                                {{ __('Sidebar enabled') }}
                                <span class="mt-1 block text-xs font-normal text-on-surface-variant">
                                    {{ __('The top navigation bar is always shown.') }}
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="rounded-2xl border border-outline-variant/15 bg-surface-container-lowest p-6 shadow-sm ring-1 ring-outline-variant/5 sm:p-8">
                    <h2 class="font-headline text-sm font-bold text-on-surface">{{ __('Brand images') }}</h2>
                    <p class="mt-1 max-w-xl text-xs text-on-surface-variant">
                        {{ __('Upload files stored on this server. Existing remote URLs in the database still work until you replace them.') }}
                    </p>
                    <div class="mt-6 space-y-6">
                        <div class="max-w-xl">
                            <label for="logo_file" class="mb-1.5 block text-sm font-medium text-on-surface">
                                {{ __('Logo') }}
                            </label>
                            @php
                                $currentLogoUrl = setting_media_url($settings['logo'] ?? null);
                            @endphp
                            @if ($currentLogoUrl)
                                <div class="mb-3 flex items-center gap-4">
                                    <img
                                        src="{{ $currentLogoUrl }}"
                                        alt=""
                                        class="h-14 w-14 rounded-xl border border-outline-variant/15 bg-surface object-cover ring-1 ring-outline-variant/10"
                                        width="56"
                                        height="56"
                                    />
                                    <span class="text-xs text-on-surface-variant">{{ __('Current logo') }}</span>
                                </div>
                            @endif
                            <input
                                id="logo_file"
                                name="logo"
                                type="file"
                                accept="image/*"
                                class="block w-full text-sm text-on-surface file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-on-primary"
                            />
                            @error('logo')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="max-w-xl">
                            <label for="favicon_file" class="mb-1.5 block text-sm font-medium text-on-surface">
                                {{ __('Favicon') }}
                            </label>
                            @php
                                $currentFaviconUrl = setting_media_url($settings['favicon'] ?? null);
                            @endphp
                            @if ($currentFaviconUrl)
                                <div class="mb-3 flex items-center gap-4">
                                    <img
                                        src="{{ $currentFaviconUrl }}"
                                        alt=""
                                        class="h-10 w-10 rounded-lg border border-outline-variant/15 bg-surface object-contain p-1 ring-1 ring-outline-variant/10"
                                        width="40"
                                        height="40"
                                    />
                                    <span class="text-xs text-on-surface-variant">{{ __('Current favicon') }}</span>
                                </div>
                            @endif
                            <input
                                id="favicon_file"
                                name="favicon"
                                type="file"
                                accept=".ico,.png,.jpg,.jpeg,.svg,.webp"
                                class="block w-full text-sm text-on-surface file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-on-primary"
                            />
                            @error('favicon')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <button
                        type="submit"
                        class="font-headline inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-bold text-on-primary shadow-sm transition hover:bg-primary-dim active:scale-[0.98]"
                    >
                        <x-lumina.icon name="save" class="!text-lg" />
                        {{ __('Save settings') }}
                    </button>
                </div>
            </form>

            <aside class="min-w-0 lg:col-span-4">
                <div
                    class="sticky top-8 rounded-2xl border-2 border-dashed border-outline-variant/30 bg-surface-container-lowest p-6 shadow-sm ring-1 ring-outline-variant/5"
                    style="border-color: color-mix(in srgb, var(--lumina-primary, #2b4adc) 45%, transparent)"
                >
                    <h2 class="font-headline text-sm font-bold text-on-surface">{{ __('Live preview') }}</h2>
                    <p class="mt-2 text-sm text-on-surface-variant">
                        {{ __('Primary actions use your primary color. Secondary applies to main text in light mode.') }}
                    </p>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <span
                            class="font-headline inline-flex rounded-xl px-4 py-2.5 text-sm font-bold text-on-primary shadow-sm"
                            style="background-color: var(--lumina-primary, #2b4adc)"
                        >
                            {{ __('Primary action') }}
                        </span>
                        <span class="text-sm" style="font-size: var(--font-size-base, 16px)">
                            {{ __('Font size sample') }}
                        </span>
                    </div>
                </div>
            </aside>
        </div>

    <script>
        (function () {
            const root = document.documentElement;
            const fontMap = { sm: '14px', md: '16px', lg: '18px' };

            function applyAppearanceFromForm() {
                const primary = document.getElementById('primary_color');
                const secondary = document.getElementById('secondary_color');
                const fontSize = document.getElementById('font_size');
                if (primary) {
                    root.style.setProperty('--lumina-primary', primary.value);
                    root.style.setProperty('--color-primary', primary.value);
                }
                if (secondary) {
                    root.style.setProperty('--lumina-on-surface', secondary.value);
                    root.style.setProperty('--color-secondary', secondary.value);
                }
                if (fontSize) {
                    root.style.setProperty('--font-size-base', fontMap[fontSize.value] || '16px');
                }
            }

            document.querySelectorAll('[data-setting-preview]').forEach((el) => {
                el.addEventListener('input', applyAppearanceFromForm);
                el.addEventListener('change', applyAppearanceFromForm);
            });

            const themeModeEl = document.getElementById('theme_mode');
            if (themeModeEl) {
                themeModeEl.addEventListener('change', function () {
                    root.setAttribute('data-theme', themeModeEl.value);
                    try {
                        sessionStorage.removeItem('lumina-appearance');
                    } catch (e) {}
                    if (window.applyThemeMode) {
                        window.applyThemeMode();
                    }
                });
            }

            applyAppearanceFromForm();
        })();
    </script>
</x-layout>
