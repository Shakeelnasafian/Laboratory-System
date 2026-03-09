<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Permission Manager')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Space Grotesk', 'ui-sans-serif', 'system-ui'],
                    },
                },
            },
        }
    </script>

    <style>
        :root {
            --pm-bg: #f6efe6;
            --pm-surface: #fff7ed;
            --pm-ink: #0f172a;
            --pm-muted: #64748b;
            --pm-accent: #0f766e;
            --pm-accent-2: #ea580c;
            --pm-stroke: rgba(15, 23, 42, 0.12);
        }
    </style>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('head')
</head>
<body class="min-h-screen bg-[var(--pm-bg)] text-[var(--pm-ink)]">
    <div class="min-h-screen lg:grid lg:grid-cols-[280px_1fr]">
        @include('permission-manager::partials._nav')

        <main class="px-6 py-8 lg:px-10">
            <div class="mx-auto max-w-6xl">
                @include('permission-manager::partials._flash')
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
