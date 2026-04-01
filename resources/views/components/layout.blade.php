@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    {{-- Tailwind: swap this script for @vite(['resources/css/app.css', ...]) when you move off the Play CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    {{-- Design tokens: edit here (or switch to tailwind.config / CSS entry) to retheme the app --}}
    <style type="text/tailwindcss">
        @theme {
            --font-sans: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
            --color-app-bg: #fdfdfc;
            --color-app-surface: #ffffff;
            --color-app-ink: #1b1b18;
            --color-app-muted: #706f6c;
            --color-app-accent: #f53003;
            --color-app-border: #e3e3e0;
        }
    </style>
</head>
<body class="min-h-full bg-app-bg font-sans text-app-ink antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <div class="flex min-h-full flex-col">
        <header class="border-b border-app-border/80 bg-app-surface/90 px-4 py-4 shadow-sm backdrop-blur-sm dark:border-zinc-800 dark:bg-zinc-900/90 sm:px-6 lg:px-8">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4">
                <a href="{{ url('/') }}" class="text-lg font-semibold tracking-tight text-app-ink dark:text-zinc-50">
                    {{ config('app.name', 'Dashboard') }}
                </a>
            </div>
        </header>

        <main class="flex flex-1 flex-col">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
