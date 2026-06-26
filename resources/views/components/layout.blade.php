@props([
    'title' => null,
    /** @var array<string, mixed> Merge with defaults; pass from controllers when settings are loaded from the DB */
    'settings' => [],
])

@php
    $defaults = [
        'theme_mode' => 'light',
        'primary_color' => '#2b4adc',
        'secondary_color' => '#2c2f32',
        'font_size' => 'md',
        'site_name' => config('app.name'),
        'navbar_enabled' => true,
        'sidebar_enabled' => true,
        'logo' => null,
        'favicon' => null,
    ];

    $serviceSettings = is_array($appSettings ?? null) ? $appSettings : [];
    $appSettings = array_merge($defaults, $serviceSettings, is_array($settings) ? $settings : []);

    $themeMode = $appSettings['theme_mode'];
    $fontSizeBase = match ($appSettings['font_size']) {
        'sm' => '14px',
        'lg' => '18px',
        default => '16px',
    };
    $primary = $appSettings['primary_color'];
    $secondary = $appSettings['secondary_color'];
    $siteName = $appSettings['site_name'];
    $initialDarkClass = $themeMode === 'dark' ? 'dark' : '';

    $navbarEnabled = true;
    $sidebarEnabled = (bool) $appSettings['sidebar_enabled'];

    $useSidebarLayout =
        auth()->check()
        && auth()->user()->hasVerifiedEmail()
        && $sidebarEnabled;

    $authUser = auth()->user();
    $userDisplayName = '';
    $initials = '';
    if ($authUser !== null) {
        $userDisplayName = $authUser->name ?? $authUser->email;
        $userParts = preg_split('/\s+/', trim($authUser->name ?? ''));
        $initials = strtoupper(
            count($userParts) >= 2
                ? mb_substr($userParts[0], 0, 1) . mb_substr($userParts[1], 0, 1)
                : mb_substr((string) $authUser->email, 0, 1)
        );
    }

    $logoUrl = setting_media_url($appSettings['logo'] ?? null);
    $faviconUrl = setting_media_url($appSettings['favicon'] ?? null);
@endphp

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="h-full {{ $initialDarkClass }}"
    data-theme="{{ $themeMode }}"
    style="--lumina-primary: {{ $primary }}; --lumina-on-surface: {{ $secondary }}; --font-size-base: {{ $fontSizeBase }}; font-size: var(--font-size-base);"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if ($useSidebarLayout)
        <script>
            try {
                if (localStorage.getItem('app-sidebar-collapsed') === '1') {
                    document.documentElement.classList.add('lumina-sidebar-collapsed');
                }
            } catch (e) {}
        </script>
    @endif

    <title>{{ $title ?? $siteName }}</title>

    @if (! empty($faviconUrl))
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        (function () {
            try {
                var o = sessionStorage.getItem('lumina-appearance');
                if (o === 'light' || o === 'dark') {
                    document.documentElement.setAttribute('data-theme', o);
                    document.documentElement.classList.toggle('dark', o === 'dark');
                }
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('layoutShell', () => ({
                mobileSidebarOpen: false,
                desktopCollapsed: localStorage.getItem('app-sidebar-collapsed') === '1',
                profileMenuOpen: false,
                layoutTransitionsEnabled: false,

                init() {
                    document.documentElement.classList.toggle('lumina-sidebar-collapsed', this.desktopCollapsed);
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            this.layoutTransitionsEnabled = true;
                        });
                    });
                },

                toggleDesktopCollapse() {
                    this.desktopCollapsed = !this.desktopCollapsed;
                    localStorage.setItem('app-sidebar-collapsed', this.desktopCollapsed ? '1' : '0');
                    document.documentElement.classList.toggle('lumina-sidebar-collapsed', this.desktopCollapsed);
                },

                isDarkActive() {
                    return document.documentElement.classList.contains('dark');
                },

                setAppearance(next) {
                    try {
                        sessionStorage.setItem('lumina-appearance', next);
                    } catch (e) {}
                    document.documentElement.setAttribute('data-theme', next);
                    if (typeof window.applyThemeMode === 'function') {
                        window.applyThemeMode();
                    }
                },
            }));
        });
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

</head>

<body class="min-h-full bg-background font-sans text-on-surface antialiased">
    @if ($useSidebarLayout)
        <div
            class="min-h-screen"
            x-data="layoutShell()"
            @keydown.window.escape="mobileSidebarOpen = false; profileMenuOpen = false"
        >
            <div
                class="fixed inset-0 z-40 bg-inverse-surface/50 backdrop-blur-sm transition-opacity lg:hidden"
                x-show="mobileSidebarOpen"
                x-transition.opacity
                x-cloak
                @click="mobileSidebarOpen = false"
                aria-hidden="true"
            ></div>

            <aside
                id="app-sidebar"
                class="fixed left-0 top-0 z-50 flex h-full flex-col border-r border-outline-variant/15 bg-surface-container-low py-6 shadow-xl max-lg:w-[min(18rem,88vw)] lg:z-20 lg:shadow-none"
                :class="{
                    'max-lg:-translate-x-full': !mobileSidebarOpen,
                    'max-lg:translate-x-0': mobileSidebarOpen,
                    'transition-[width,transform] duration-300 ease-out': layoutTransitionsEnabled,
                }"
                aria-label="{{ __('Main navigation') }}"
            >
                <div class="mb-8 flex items-center gap-3 px-6" :class="desktopCollapsed ? 'lg:justify-center lg:px-3' : ''">
                    <a
                        href="{{ auth()->check() ? route('profile.edit') : url('/') }}"
                        class="flex min-w-0 flex-1 items-center gap-3"
                        :class="desktopCollapsed ? 'lg:justify-center' : ''"
                    >
                        @if (! empty($logoUrl))
                            <img
                                src="{{ $logoUrl }}"
                                alt=""
                                class="h-10 w-10 shrink-0 rounded-xl object-cover ring-2 ring-primary/20"
                                width="40"
                                height="40"
                            />
                        @else
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary text-on-primary">
                                <x-lumina.icon name="dashboard" class="!text-2xl text-on-primary" />
                            </div>
                        @endif
                        <div class="min-w-0" x-show="!desktopCollapsed" x-cloak>
                            <h1 class="truncate font-headline text-lg font-bold leading-none tracking-tight text-on-surface">{{ $siteName }}</h1>
                        </div>
                    </a>
                    <button
                        type="button"
                        class="hidden rounded-full p-2 text-on-surface-variant transition hover:bg-surface-container-high active:scale-95 lg:inline-flex"
                        @click="toggleDesktopCollapse()"
                        :title="desktopCollapsed ? '{{ __('Expand sidebar') }}' : '{{ __('Collapse sidebar') }}'"
                        aria-controls="app-sidebar"
                    >
                        <x-lumina.icon name="chevron_left" class="transition-transform duration-300" x-bind:class="{ 'rotate-180': desktopCollapsed }" />
                    </button>
                    <button
                        type="button"
                        class="rounded-full p-2 text-on-surface-variant transition hover:bg-surface-container-high lg:hidden"
                        @click="mobileSidebarOpen = false"
                        aria-label="{{ __('Close menu') }}"
                    >
                        <x-lumina.icon name="close" />
                    </button>
                </div>

                <nav class="flex flex-1 flex-col gap-1 overflow-y-auto pr-2 text-sm font-medium" aria-label="{{ __('Main') }}">
                    @can(\App\Enums\PermissionsEnum::PERMISSION_VIEW->value)
                        <a
                            href="{{ route('permissions.index') }}"
                            @class([
                                'flex items-center gap-3 border-l-4 py-3 transition-all duration-200',
                                'rounded-r-full bg-primary/10 pl-5 pr-4 font-medium text-primary border-primary' => request()->routeIs('permissions.*'),
                                'border-transparent pl-6 pr-4 text-on-surface-variant hover:bg-surface-container-high' => ! request()->routeIs('permissions.*'),
                            ])
                            :class="desktopCollapsed ? 'lg:justify-center lg:border-l-0 lg:px-2 lg:rounded-xl' : ''"
                        >
                            <x-lumina.icon name="verified_user" :filled="request()->routeIs('permissions.*')" />
                            <span class="truncate max-lg:inline" x-show="!desktopCollapsed" x-cloak>{{ __('Permissions') }}</span>
                        </a>
                    @endcan
                    @can(\App\Enums\PermissionsEnum::ROLE_VIEW->value)
                        <a
                            href="{{ route('roles.index') }}"
                            @class([
                                'flex items-center gap-3 border-l-4 py-3 transition-all duration-200',
                                'rounded-r-full bg-primary/10 pl-5 pr-4 font-medium text-primary border-primary' => request()->routeIs('roles.*'),
                                'border-transparent pl-6 pr-4 text-on-surface-variant hover:bg-surface-container-high' => ! request()->routeIs('roles.*'),
                            ])
                            :class="desktopCollapsed ? 'lg:justify-center lg:border-l-0 lg:px-2 lg:rounded-xl' : ''"
                        >
                            <x-lumina.icon name="group" :filled="request()->routeIs('roles.*')" />
                            <span class="truncate max-lg:inline" x-show="!desktopCollapsed" x-cloak>{{ __('Roles') }}</span>
                        </a>
                    @endcan
                    @can(\App\Enums\PermissionsEnum::USER_VIEW->value)
                        <a
                            href="{{ route('users.index') }}"
                            @class([
                                'flex items-center gap-3 border-l-4 py-3 transition-all duration-200',
                                'rounded-r-full bg-primary/10 pl-5 pr-4 font-medium text-primary border-primary' => request()->routeIs('users.*'),
                                'border-transparent pl-6 pr-4 text-on-surface-variant hover:bg-surface-container-high' => ! request()->routeIs('users.*'),
                            ])
                            :class="desktopCollapsed ? 'lg:justify-center lg:border-l-0 lg:px-2 lg:rounded-xl' : ''"
                        >
                            <x-lumina.icon name="person" :filled="request()->routeIs('users.*')" />
                            <span class="truncate max-lg:inline" x-show="!desktopCollapsed" x-cloak>{{ __('Users') }}</span>
                        </a>
                    @endcan
                    @can(\App\Enums\PermissionsEnum::SHOP_VIEW->value)
                        <a
                            href="{{ route('shops.index') }}"
                            @class([
                                'flex items-center gap-3 border-l-4 py-3 transition-all duration-200',
                                'rounded-r-full bg-primary/10 pl-5 pr-4 font-medium text-primary border-primary' => request()->routeIs('shops.*'),
                                'border-transparent pl-6 pr-4 text-on-surface-variant hover:bg-surface-container-high' => ! request()->routeIs('shops.*'),
                            ])
                            :class="desktopCollapsed ? 'lg:justify-center lg:border-l-0 lg:px-2 lg:rounded-xl' : ''"
                        >
                            <x-lumina.icon name="storefront" :filled="request()->routeIs('shops.*')" />
                            <span class="truncate max-lg:inline" x-show="!desktopCollapsed" x-cloak>{{ __('Shops') }}</span>
                        </a>
                    @endcan
                    @can(\App\Enums\PermissionsEnum::ORDER_VIEW->value)
                        <a
                            href="{{ route('orders.index') }}"
                            @class([
                                'flex items-center gap-3 border-l-4 py-3 transition-all duration-200',
                                'rounded-r-full bg-primary/10 pl-5 pr-4 font-medium text-primary border-primary' => request()->routeIs('orders.*'),
                                'border-transparent pl-6 pr-4 text-on-surface-variant hover:bg-surface-container-high' => ! request()->routeIs('orders.*'),
                            ])
                            :class="desktopCollapsed ? 'lg:justify-center lg:border-l-0 lg:px-2 lg:rounded-xl' : ''"
                        >
                            <x-lumina.icon name="receipt_long" :filled="request()->routeIs('orders.*')" />
                            <span class="truncate max-lg:inline" x-show="!desktopCollapsed" x-cloak>{{ __('Orders') }}</span>
                        </a>
                    @endcan
                    @can(\App\Enums\PermissionsEnum::PACKAGE_VIEW->value)
                        <a
                            href="{{ route('packages.index') }}"
                            @class([
                                'flex items-center gap-3 border-l-4 py-3 transition-all duration-200',
                                'rounded-r-full bg-primary/10 pl-5 pr-4 font-medium text-primary border-primary' => request()->routeIs('packages.*'),
                                'border-transparent pl-6 pr-4 text-on-surface-variant hover:bg-surface-container-high' => ! request()->routeIs('packages.*'),
                            ])
                            :class="desktopCollapsed ? 'lg:justify-center lg:border-l-0 lg:px-2 lg:rounded-xl' : ''"
                        >
                            <x-lumina.icon name="inventory_2" :filled="request()->routeIs('packages.*')" />
                            <span class="truncate max-lg:inline" x-show="!desktopCollapsed" x-cloak>{{ __('Packages') }}</span>
                        </a>
                    @endcan
                    @can(\App\Enums\PermissionsEnum::EXTERNAL_SYSTEM_VIEW->value)
                        <a
                            href="{{ route('external-systems.index') }}"
                            @class([
                                'flex items-center gap-3 border-l-4 py-3 transition-all duration-200',
                                'rounded-r-full bg-primary/10 pl-5 pr-4 font-medium text-primary border-primary' => request()->routeIs('external-systems.*'),
                                'border-transparent pl-6 pr-4 text-on-surface-variant hover:bg-surface-container-high' => ! request()->routeIs('external-systems.*'),
                            ])
                            :class="desktopCollapsed ? 'lg:justify-center lg:border-l-0 lg:px-2 lg:rounded-xl' : ''"
                        >
                            <x-lumina.icon name="hub" :filled="request()->routeIs('external-systems.*')" />
                            <span class="truncate max-lg:inline" x-show="!desktopCollapsed" x-cloak>{{ __('External systems') }}</span>
                        </a>
                    @endcan
                    @if (auth()->user()?->can(\App\Enums\PermissionsEnum::SETTINGS_VIEW->value) && auth()->user()?->can(\App\Enums\PermissionsEnum::SETTINGS_UPDATE->value))
                        <a
                            href="{{ route('settings.edit') }}"
                            @class([
                                'flex items-center gap-3 border-l-4 py-3 transition-all duration-200',
                                'rounded-r-full bg-primary/10 pl-5 pr-4 font-medium text-primary border-primary' => request()->routeIs('settings.*'),
                                'border-transparent pl-6 pr-4 text-on-surface-variant hover:bg-surface-container-high' => ! request()->routeIs('settings.*'),
                            ])
                            :class="desktopCollapsed ? 'lg:justify-center lg:border-l-0 lg:px-2 lg:rounded-xl' : ''"
                        >
                            <x-lumina.icon name="settings" :filled="request()->routeIs('settings.*')" />
                            <span class="truncate max-lg:inline" x-show="!desktopCollapsed" x-cloak>{{ __('Settings') }}</span>
                        </a>
                    @endif
                </nav>

                <div class="mt-auto px-6 pt-4" :class="desktopCollapsed ? 'lg:px-2' : ''">
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-xl py-3 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container-high active:scale-[0.98]"
                            :class="desktopCollapsed ? 'lg:px-0' : 'gap-3 px-2'"
                        >
                            <x-lumina.icon name="logout" />
                            <span x-show="!desktopCollapsed" x-cloak>{{ __('Sign out') }}</span>
                        </button>
                    </form>
                </div>
            </aside>

            <div
                id="lumina-main-column"
                class="flex min-h-screen min-w-0 flex-1 flex-col"
                :class="{ 'transition-[margin-left,width] duration-300 ease-out': layoutTransitionsEnabled }"
            >
                @if ($navbarEnabled)
                    <header class="sticky top-0 z-40 border-b border-outline-variant/10 bg-surface backdrop-blur-sm">
                        <div class="mx-auto flex h-16 w-full max-w-8xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <button
                                type="button"
                                class="inline-flex rounded-full p-2 text-on-surface-variant transition hover:bg-surface-container-high active:scale-95 lg:hidden"
                                @click="mobileSidebarOpen = true"
                                aria-label="{{ __('Open menu') }}"
                            >
                                <x-lumina.icon name="menu" class="!text-2xl" />
                            </button>
                        </div>
                        <div class="flex shrink-0 items-center gap-3 sm:gap-6">
                            <div
                                class="inline-flex rounded-full bg-surface-container-low p-1 shadow-inner ring-1 ring-outline-variant/10"
                                role="group"
                                aria-label="{{ __('Theme') }}"
                            >
                                <button
                                    type="button"
                                    class="flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-bold transition active:scale-95"
                                    :class="!isDarkActive() ? 'bg-surface-container-lowest text-primary shadow-sm' : 'text-on-surface-variant hover:text-on-surface'"
                                    @click="setAppearance('light')"
                                >
                                    <x-lumina.icon name="light_mode" class="!text-base" />
                                    <span class="hidden sm:inline">{{ __('Light') }}</span>
                                </button>
                                <button
                                    type="button"
                                    class="flex items-center gap-1.5 rounded-full px-3 py-2 text-xs font-bold transition active:scale-95"
                                    :class="isDarkActive() ? 'bg-surface-container-lowest text-primary shadow-sm' : 'text-on-surface-variant hover:text-on-surface'"
                                    @click="setAppearance('dark')"
                                >
                                    <x-lumina.icon name="dark_mode" class="!text-base" />
                                    <span class="hidden sm:inline">{{ __('Dark') }}</span>
                                </button>
                            </div>
                            <div class="hidden h-8 w-px bg-outline-variant/30 sm:block" aria-hidden="true"></div>
                            <div class="relative">
                                <button
                                    type="button"
                                    class="flex items-center gap-2 rounded-xl py-1 pl-1 pr-1.5 transition hover:bg-surface-container-high sm:gap-3 sm:pr-2"
                                    title="{{ __('Account menu') }}"
                                    aria-expanded="false"
                                    aria-haspopup="true"
                                    x-bind:aria-expanded="profileMenuOpen"
                                    @click="profileMenuOpen = !profileMenuOpen"
                                >
                                    <div class="hidden text-right sm:block">
                                        <p class="text-xs font-bold leading-none text-on-surface">{{ $userDisplayName }}</p>
                                        <p class="mt-0.5 text-[10px] text-on-surface-variant">{{ $authUser->email }}</p>
                                    </div>
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary/15 font-headline text-sm font-bold text-primary ring-2 ring-primary/25"
                                        aria-hidden="true"
                                    >
                                        {{ $initials }}
                                    </div>
                                    <x-lumina.icon name="expand_more" class="hidden !text-lg text-on-surface-variant sm:block" x-bind:class="{ 'rotate-180': profileMenuOpen }" />
                                </button>
                                <div
                                    x-show="profileMenuOpen"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="scale-95 opacity-0"
                                    x-transition:enter-end="scale-100 opacity-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="scale-100 opacity-100"
                                    x-transition:leave-end="scale-95 opacity-0"
                                    x-cloak
                                    @click.outside="profileMenuOpen = false"
                                    class="absolute right-0 top-full z-50 mt-2 w-56 origin-top-right rounded-xl border border-outline-variant/15 bg-surface-container-lowest py-1 shadow-xl ring-1 ring-black/5"
                                    role="menu"
                                >
                                    <a
                                        href="{{ route('profile.edit') }}"
                                        class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-on-surface transition hover:bg-surface-container-high"
                                        role="menuitem"
                                        @click="profileMenuOpen = false"
                                    >
                                        <x-lumina.icon name="account_circle" class="!text-xl text-on-surface-variant" />
                                        {{ __('Profile') }}
                                    </a>
                                    @if (auth()->user()?->can(\App\Enums\PermissionsEnum::SETTINGS_VIEW->value) && auth()->user()?->can(\App\Enums\PermissionsEnum::SETTINGS_UPDATE->value))
                                        <a
                                            href="{{ route('settings.edit') }}"
                                            class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-on-surface transition hover:bg-surface-container-high"
                                            role="menuitem"
                                            @click="profileMenuOpen = false"
                                        >
                                            <x-lumina.icon name="settings" class="!text-xl text-on-surface-variant" />
                                            {{ __('Settings') }}
                                        </a>
                                    @endif
                                    <div class="my-1 border-t border-outline-variant/15" role="none"></div>
                                    <form method="post" action="{{ route('logout') }}" role="none">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-sm font-medium text-error transition hover:bg-error/10"
                                            role="menuitem"
                                        >
                                            <x-lumina.icon name="logout" class="!text-xl" />
                                            {{ __('Sign out') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        </div>
                    </header>
                @else
                    <header class="sticky top-0 z-30 flex h-12 justify-end border-b border-outline-variant/10 bg-surface px-4">
                        <button
                            type="button"
                            class="mr-auto inline-flex rounded-full p-2 text-on-surface-variant hover:bg-surface-container-high lg:hidden"
                            @click="mobileSidebarOpen = true"
                            aria-label="{{ __('Open menu') }}"
                        >
                            <x-lumina.icon name="menu" class="!text-2xl" />
                        </button>
                    </header>
                @endif

                <main class="flex min-h-0 flex-1 flex-col bg-surface-container-lowest">
                    <div class="mx-auto w-full min-w-0 max-w-none flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    @elseif (auth()->check())
        <div class="flex min-h-screen flex-col bg-surface-container-lowest">
            @if ($navbarEnabled)
                <header class="border-b border-outline-variant/10 bg-surface px-4 py-4 shadow-sm backdrop-blur-sm sm:px-6 lg:px-8">
                    <div class="mx-auto flex w-full min-w-0 max-w-7xl flex-wrap items-center justify-between gap-4">
                        <a href="{{ auth()->user()->hasVerifiedEmail() ? route('profile.edit') : url('/') }}" class="flex items-center gap-3 font-headline text-lg font-bold tracking-tight text-on-surface">
                            @if (! empty($logoUrl))
                                <img src="{{ $logoUrl }}" alt="" class="h-8 w-auto max-w-[10rem] object-contain" width="120" height="32" />
                            @endif
                            <span>{{ $siteName }}</span>
                        </a>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                            @if (auth()->user()->hasVerifiedEmail())
                                <nav class="flex flex-wrap items-center gap-x-1 gap-y-2 text-sm font-medium" aria-label="{{ __('Main') }}">
                                    <a href="{{ route('profile.edit') }}" class="rounded-md px-2.5 py-1.5 text-on-surface-variant transition hover:bg-surface-container-high hover:text-on-surface">{{ __('Profile') }}</a>
                                    @role(\App\Enums\RolesEnum::SUPER_ADMIN->value)
                                        @can(\App\Enums\PermissionsEnum::PERMISSION_VIEW->value)
                                            <a href="{{ route('permissions.index') }}" class="rounded-md px-2.5 py-1.5 text-on-surface-variant transition hover:bg-surface-container-high hover:text-on-surface">{{ __('Permissions') }}</a>
                                        @endcan
                                    @endrole
                                    @can(\App\Enums\PermissionsEnum::ROLE_VIEW->value)
                                        <a href="{{ route('roles.index') }}" class="rounded-md px-2.5 py-1.5 text-on-surface-variant transition hover:bg-surface-container-high hover:text-on-surface">{{ __('Roles') }}</a>
                                    @endcan
                                    @can(\App\Enums\PermissionsEnum::USER_VIEW->value)
                                        <a href="{{ route('users.index') }}" class="rounded-md px-2.5 py-1.5 text-on-surface-variant transition hover:bg-surface-container-high hover:text-on-surface">{{ __('Users') }}</a>
                                    @endcan
                                    @can(\App\Enums\PermissionsEnum::SHOP_VIEW->value)
                                        <a href="{{ route('shops.index') }}" class="rounded-md px-2.5 py-1.5 text-on-surface-variant transition hover:bg-surface-container-high hover:text-on-surface">{{ __('Shops') }}</a>
                                    @endcan
                                    @can(\App\Enums\PermissionsEnum::ORDER_VIEW->value)
                                        <a href="{{ route('orders.index') }}" class="rounded-md px-2.5 py-1.5 text-on-surface-variant transition hover:bg-surface-container-high hover:text-on-surface">{{ __('Orders') }}</a>
                                    @endcan
                                    @can(\App\Enums\PermissionsEnum::PACKAGE_VIEW->value)
                                        <a href="{{ route('packages.index') }}" class="rounded-md px-2.5 py-1.5 text-on-surface-variant transition hover:bg-surface-container-high hover:text-on-surface">{{ __('Packages') }}</a>
                                    @endcan
                                    @role(\App\Enums\RolesEnum::SUPER_ADMIN->value)
                                        @can(\App\Enums\PermissionsEnum::EXTERNAL_SYSTEM_VIEW->value)
                                            <a href="{{ route('external-systems.index') }}" class="rounded-md px-2.5 py-1.5 text-on-surface-variant transition hover:bg-surface-container-high hover:text-on-surface">{{ __('External systems') }}</a>
                                        @endcan
                                    @endrole
                                    @if (auth()->user()?->can(\App\Enums\PermissionsEnum::SETTINGS_VIEW->value) && auth()->user()?->can(\App\Enums\PermissionsEnum::SETTINGS_UPDATE->value))
                                        <a href="{{ route('settings.edit') }}" class="rounded-md px-2.5 py-1.5 text-on-surface-variant transition hover:bg-surface-container-high hover:text-on-surface">{{ __('Settings') }}</a>
                                    @endif
                                </nav>
                            @endif
                            <form method="post" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="rounded-md px-2.5 py-1.5 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container-high hover:text-on-surface">{{ __('Sign out') }}</button>
                            </form>
                        </div>
                    </div>
                </header>
            @else
                <header class="flex justify-end border-b border-outline-variant/10 bg-surface px-4 py-2">
                    <form method="post" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="rounded-md px-2.5 py-1.5 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container-high hover:text-on-surface">{{ __('Sign out') }}</button>
                    </form>
                </header>
            @endif

            <main class="flex flex-1 flex-col">
                <div class="mx-auto w-full min-w-0 max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    {{ $slot }}
                </div>
            </main>
        </div>
    @else
        <div class="flex min-h-screen flex-col bg-surface-container-lowest">
            @if ($navbarEnabled)
                <header class="border-b border-outline-variant/10 bg-surface px-4 py-4 sm:px-6 lg:px-8">
                    <div class="mx-auto flex w-full min-w-0 max-w-7xl items-center justify-between">
                        <a href="{{ url('/') }}" class="flex items-center gap-3 font-headline text-lg font-bold tracking-tight text-on-surface">
                            @if (! empty($logoUrl))
                                <img src="{{ $logoUrl }}" alt="" class="h-8 w-auto object-contain" width="120" height="32" />
                            @endif
                            <span>{{ $siteName }}</span>
                        </a>
                    </div>
                </header>
            @endif
            <main class="flex flex-1 flex-col">
                {{ $slot }}
            </main>
        </div>
    @endif

    <script>
        window.appSettings = @json($appSettings);

        window.applyThemeMode = function () {
            let mode = document.documentElement.getAttribute('data-theme') || 'light';
            try {
                var o = sessionStorage.getItem('lumina-appearance');
                if (o === 'light' || o === 'dark') {
                    mode = o;
                    document.documentElement.setAttribute('data-theme', mode);
                }
            } catch (e) {}

            const root = document.documentElement;
            root.classList.remove('dark');

            if (mode === 'dark') {
                root.classList.add('dark');
            } else if (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                root.classList.add('dark');
            }
        };
        window.applyThemeMode();
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (document.documentElement.getAttribute('data-theme') === 'system') {
                try {
                    if (sessionStorage.getItem('lumina-appearance')) {
                        return;
                    }
                } catch (e) {}
                window.applyThemeMode();
            }
        });
    </script>
</body>
</html>
