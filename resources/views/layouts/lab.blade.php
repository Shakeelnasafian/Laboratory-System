<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - {{ auth()->user()->lab->name ?? 'Lab' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        :root {
            {!! cache()->rememberForever('ui.theme.css_vars', fn () =>
                collect(\Illuminate\Support\Arr::dot(config('ui.theme')))
                    ->map(fn ($value, $key) => '--ui-' . str_replace(['.', '_'], '-', $key) . ': ' . e($value) . ';')
                    ->implode("\n            ")
            ) !!}
        }
    </style>
</head>
<body class="h-full bg-gray-50 font-sans antialiased" x-data>
<div class="relative flex h-full overflow-hidden">
    <div
        x-cloak
        x-show="$store.labSidebar.open && !$store.labSidebar.isDesktop"
        x-transition.opacity
        class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"
        @click="$store.labSidebar.closeMenu()"
    ></div>

    @if (app()->runningUnitTests())
        @include('layouts.partials.lab-sidebar')
    @else
        @persist('lab-sidebar')
            @include('layouts.partials.lab-sidebar')
        @endpersist
    @endif

    <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
        <header class="border-b border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-100 lg:hidden"
                        @click="$store.labSidebar.openMenu()"
                    >
                        <span class="sr-only">Open menu</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="hidden h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-100 lg:inline-flex"
                        @click="$store.labSidebar.toggleMini()"
                    >
                        <span class="sr-only">Toggle sidebar width</span>
                        <svg x-show="!$store.labSidebar.mini" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19l-7-7 7-7" />
                        </svg>
                        <svg x-show="$store.labSidebar.mini" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <div>
                        <h1 class="text-lg font-semibold text-gray-800 sm:text-2xl">{{ $title ?? 'Dashboard' }}</h1>
                        <p class="text-xs text-gray-500 sm:hidden">{{ auth()->user()->lab->name ?? 'Lab' }}</p>
                    </div>
                </div>

                <div class="text-xs text-gray-500 sm:text-sm">{{ now()->format('D, d M Y') }}</div>
            </div>
        </header>

        <div class="px-4 pt-4 sm:px-6">
            @if(session('success'))
                <div class="mb-2 rounded-xl border border-green-300 bg-green-100 px-4 py-2 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-2 rounded-xl border border-red-300 bg-red-100 px-4 py-2 text-sm text-red-800">{{ session('error') }}</div>
            @endif
            @php $lab = auth()->user()?->lab; @endphp
            @if($lab && $lab->isSubscriptionExpired())
                <div class="mb-2 rounded-xl border border-red-400 bg-red-50 px-4 py-2 text-sm text-red-800 font-medium">
                    Your subscription has expired. Please contact the administrator to renew access.
                </div>
            @elseif($lab && $lab->subscriptionDaysLeft() <= 14)
                <div class="mb-2 rounded-xl border border-yellow-400 bg-yellow-50 px-4 py-2 text-sm text-yellow-800">
                    Your subscription expires in {{ $lab->subscriptionDaysLeft() }} day(s). Please renew soon.
                </div>
            @endif
        </div>

        <main class="min-w-0 flex-1 overflow-y-auto p-4 sm:p-6">
            {{ $slot }}
        </main>
    </div>
</div>
@livewireScripts
</body>
</html>
