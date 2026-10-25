<aside
    class="fixed inset-y-0 left-0 z-40 flex h-dvh max-h-dvh min-h-0 flex-col overflow-hidden bg-blue-900 text-white shadow-2xl transition-all duration-300 ease-out lg:static lg:h-full lg:max-h-full lg:translate-x-0 lg:shadow-none"
    :class="[
        $store.labSidebar.open || $store.labSidebar.isDesktop ? 'translate-x-0' : '-translate-x-full',
        $store.labSidebar.mini && $store.labSidebar.isDesktop ? 'w-[5.5rem]' : 'w-72'
    ]"
>
    <div
        class="border-b border-blue-800"
        :class="$store.labSidebar.mini && $store.labSidebar.isDesktop ? 'px-3 py-5' : 'px-6 py-5'"
    >
        <div class="flex items-start justify-between gap-3">
            <div
                class="flex min-w-0 items-center"
                :class="$store.labSidebar.mini && $store.labSidebar.isDesktop ? 'justify-center w-full' : 'gap-3'"
            >
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-400 text-base font-bold">
                    {{ strtoupper(substr(auth()->user()->lab->name ?? 'L', 0, 1)) }}
                </div>

                <div class="min-w-0 text-sm" x-show="!$store.labSidebar.mini || !$store.labSidebar.isDesktop" x-transition.opacity.duration.150ms>
                    <p class="truncate font-semibold leading-tight">{{ auth()->user()->lab->name ?? 'Lab' }}</p>
                    <p class="truncate text-xs capitalize text-blue-300">{{ auth()->user()->getRoleNames()->first() }}</p>
                </div>
            </div>

            <button
                type="button"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-blue-700 text-blue-100 transition hover:bg-blue-800 hover:text-white lg:hidden"
                x-show="!$store.labSidebar.mini || !$store.labSidebar.isDesktop"
                @click="$store.labSidebar.closeMenu()"
            >
                <span class="sr-only">Close menu</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>
    </div>

    <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto overscroll-y-contain px-3 py-4">
        <x-lab-nav-link :href="route('lab.dashboard')" :active="request()->routeIs('lab.dashboard')">
            <x-slot name="icon"><x-sidebar-icon name="dashboard" /></x-slot> Dashboard
        </x-lab-nav-link>

        {{-- Front desk registers orders and takes money; the lab works samples. --}}
        <x-lab-nav-link :href="route('lab.orders.create')" :active="request()->routeIs('lab.orders.create')">
            <x-slot name="icon"><x-sidebar-icon name="new-order" /></x-slot> New Order
        </x-lab-nav-link>
        <x-lab-nav-link :href="route('lab.orders.index')" :active="request()->routeIs('lab.orders.index') || request()->routeIs('lab.orders.show')">
            <x-slot name="icon"><x-sidebar-icon name="orders" /></x-slot> Orders
        </x-lab-nav-link>
        <x-lab-nav-link :href="route('lab.samples.index')" :active="request()->routeIs('lab.samples.index', 'lab.samples.show')">
            <x-slot name="icon"><x-sidebar-icon name="samples" /></x-slot> Samples
        </x-lab-nav-link>
        <x-lab-nav-link :href="route('lab.patients.index')" :active="request()->routeIs('lab.patients*')">
            <x-slot name="icon"><x-sidebar-icon name="patients" /></x-slot> Patients
        </x-lab-nav-link>
        <x-lab-nav-link :href="route('lab.invoices.index')" :active="request()->routeIs('lab.invoices*')">
            <x-slot name="icon"><x-sidebar-icon name="billing" /></x-slot> Billing
        </x-lab-nav-link>

        {{-- Per-step screens for larger labs with separate staff per bench. --}}
        @php
            $advancedActive = request()->routeIs('lab.samples.collection', 'lab.samples.receive', 'lab.samples.rejected', 'lab.worklists.*', 'lab.results.*');
        @endphp
        <div x-data="{ open: {{ $advancedActive ? 'true' : 'false' }} }">
            <button
                type="button"
                @click="open = !open"
                class="flex w-full items-center justify-between px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider text-blue-400 hover:text-blue-200"
                x-show="!$store.labSidebar.mini || !$store.labSidebar.isDesktop"
            >
                Advanced workflow
                <svg class="h-3.5 w-3.5 transition" :class="open && 'rotate-180'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div class="space-y-1" x-show="open || ($store.labSidebar.mini && $store.labSidebar.isDesktop)" x-cloak>
                <x-lab-nav-link :href="route('lab.samples.collection')" :active="request()->routeIs('lab.samples.collection', 'lab.samples.receive', 'lab.samples.rejected')">
                    <x-slot name="icon"><x-sidebar-icon name="samples" /></x-slot> Sample collection
                </x-lab-nav-link>
                <x-lab-nav-link :href="route('lab.worklists.index')" :active="request()->routeIs('lab.worklists.*')">
                    <x-slot name="icon"><x-sidebar-icon name="worklists" /></x-slot> Worklists
                </x-lab-nav-link>
                <x-lab-nav-link :href="route('lab.results.index')" :active="request()->routeIs('lab.results.index')">
                    <x-slot name="icon"><x-sidebar-icon name="results" /></x-slot> Results
                </x-lab-nav-link>
                <x-lab-nav-link :href="route('lab.results.release')" :active="request()->routeIs('lab.results.release')">
                    <x-slot name="icon"><x-sidebar-icon name="release" /></x-slot> Result Release
                </x-lab-nav-link>
            </div>
        </div>

        @role('lab_admin')
            <p
                class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider text-blue-400"
                x-show="!$store.labSidebar.mini || !$store.labSidebar.isDesktop"
                x-transition.opacity.duration.150ms
            >
                Setup
            </p>
            <x-lab-nav-link :href="route('lab.test-categories.index')" :active="request()->routeIs('lab.test-categories*')">
                <x-slot name="icon"><x-sidebar-icon name="categories" /></x-slot> Test Categories
            </x-lab-nav-link>
            <x-lab-nav-link :href="route('lab.tests.index')" :active="request()->routeIs('lab.tests*')">
                <x-slot name="icon"><x-sidebar-icon name="catalog" /></x-slot> Test Catalog
            </x-lab-nav-link>
            <x-lab-nav-link :href="route('lab.users.index')" :active="request()->routeIs('lab.users*')">
                <x-slot name="icon"><x-sidebar-icon name="users" /></x-slot> Staff Users
            </x-lab-nav-link>
            <x-lab-nav-link :href="route('lab.settings')" :active="request()->routeIs('lab.settings')">
                <x-slot name="icon"><x-sidebar-icon name="settings" /></x-slot> Settings
            </x-lab-nav-link>
        @endrole

        <div class="mt-4 border-t border-blue-800 pt-3">
            <x-lab-nav-link :href="route('lab.changelog')" :active="request()->routeIs('lab.changelog')">
                <x-slot name="icon"><x-sidebar-icon name="changelog" /></x-slot> Change Log
            </x-lab-nav-link>
        </div>
    </nav>

    {{-- Collapse / expand the sidebar (desktop only; mobile uses the drawer). --}}
    <div class="hidden shrink-0 border-t border-blue-800 px-3 py-2 lg:block">
        <button
            type="button"
            @click="$store.labSidebar.toggleMini()"
            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs text-blue-200 transition hover:bg-blue-800 hover:text-white"
            :class="$store.labSidebar.mini ? 'justify-center' : ''"
            :title="$store.labSidebar.mini ? 'Expand sidebar' : 'Collapse sidebar'"
        >
            <svg x-show="!$store.labSidebar.mini" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            </svg>
            <svg x-show="$store.labSidebar.mini" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
            </svg>
            <span x-show="!$store.labSidebar.mini">Collapse sidebar</span>
        </button>
    </div>

    <div class="shrink-0 border-t border-blue-800 px-4 py-3">
        <div class="flex items-center gap-3" :class="$store.labSidebar.mini && $store.labSidebar.isDesktop ? 'justify-center' : 'justify-between'">
            <div class="min-w-0" x-show="!$store.labSidebar.mini || !$store.labSidebar.isDesktop" x-transition.opacity.duration.150ms>
                <p class="truncate text-sm font-medium">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-blue-300">{{ auth()->user()->email }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-xs text-blue-300 transition hover:text-white" title="Logout">Logout</button>
            </form>
        </div>
    </div>
</aside>
