@php
    $navItems = [
        [
            'label' => 'Dashboard',
            'route' => 'permission-manager.dashboard',
            'pattern' => 'permission-manager.dashboard',
        ],
        [
            'label' => 'Roles',
            'route' => 'permission-manager.roles.index',
            'pattern' => 'permission-manager.roles.*',
        ],
        [
            'label' => 'Permissions',
            'route' => 'permission-manager.permissions.index',
            'pattern' => 'permission-manager.permissions.*',
        ],
        [
            'label' => 'Users',
            'route' => 'permission-manager.users.index',
            'pattern' => 'permission-manager.users.*',
        ],
        [
            'label' => 'Audit Log',
            'route' => 'permission-manager.audit.index',
            'pattern' => 'permission-manager.audit.*',
        ],
        [
            'label' => 'Import / Export',
            'route' => 'permission-manager.import-export.index',
            'pattern' => 'permission-manager.import-export.*',
        ],
    ];
@endphp

<aside x-data="{ open: false }" class="border-b border-[var(--pm-stroke)] bg-white/80 backdrop-blur lg:min-h-screen lg:border-b-0 lg:border-r">
    <div class="flex items-center justify-between px-6 py-5 lg:py-8">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-[var(--pm-muted)]">Permission</p>
            <p class="text-xl font-semibold">Manager</p>
        </div>
        <button
            type="button"
            class="rounded-lg border border-[var(--pm-stroke)] px-3 py-2 text-sm text-[var(--pm-muted)] lg:hidden"
            @click="open = !open"
        >
            Menu
        </button>
    </div>

    <nav :class="open ? 'block' : 'hidden'" class="px-4 pb-6 lg:block">
        <div class="space-y-2">
            @foreach ($navItems as $item)
                @php
                    $active = request()->routeIs($item['pattern']);
                @endphp
                <a
                    href="{{ route($item['route']) }}"
                    class="group flex items-center justify-between rounded-2xl border px-4 py-3 transition {{ $active ? 'border-[var(--pm-accent)] bg-[var(--pm-accent)] text-white shadow-lg shadow-emerald-200/40' : 'border-transparent bg-transparent text-[var(--pm-ink)] hover:border-[var(--pm-stroke)] hover:bg-[var(--pm-surface)]' }}"
                >
                    <span class="text-sm font-medium">{{ $item['label'] }}</span>
                    <span class="text-xs {{ $active ? 'text-white/80' : 'text-[var(--pm-muted)]' }}">-&gt;</span>
                </a>
            @endforeach
        </div>
    </nav>
</aside>
