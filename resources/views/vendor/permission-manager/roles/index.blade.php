@extends('permission-manager::layouts.app')

@section('title', 'Roles')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold">Roles</h1>
            <p class="mt-2 text-[var(--pm-muted)]">Manage role definitions and hierarchy.</p>
        </div>
        <a href="{{ route('permission-manager.roles.create') }}" class="rounded-full bg-[var(--pm-accent)] px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-200/50">Create Role</a>
    </div>

    <div class="mt-6 rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-5">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search roles..."
                class="w-full max-w-md rounded-2xl border border-[var(--pm-stroke)] bg-white px-4 py-2 text-sm focus:border-[var(--pm-accent)] focus:outline-none"
            >
            <button class="rounded-full border border-[var(--pm-stroke)] px-4 py-2 text-sm hover:bg-[var(--pm-surface)]">Search</button>
        </form>
    </div>

    <div class="mt-6 overflow-hidden rounded-3xl border border-[var(--pm-stroke)] bg-white/80">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-[var(--pm-stroke)] bg-[var(--pm-surface)] text-xs uppercase tracking-wider text-[var(--pm-muted)]">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Guard</th>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3">Hierarchy</th>
                    <th class="px-4 py-3">Super</th>
                    <th class="px-4 py-3">Permissions</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                    <tr class="border-b border-[var(--pm-stroke)] last:border-b-0">
                        <td class="px-4 py-3 font-medium">{{ $role->name }}</td>
                        <td class="px-4 py-3 text-[var(--pm-muted)]">{{ $role->guard_name }}</td>
                        <td class="px-4 py-3 text-[var(--pm-muted)]">{{ $role->description ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $role->hierarchy_level }}</td>
                        <td class="px-4 py-3">
                            @if ($role->is_super_admin)
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Super</span>
                            @else
                                <span class="text-xs text-[var(--pm-muted)]">No</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $role->permissions_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a class="text-sm font-semibold text-[var(--pm-accent)]" href="{{ route('permission-manager.roles.show', $role) }}">View</a>
                                <a class="text-sm font-semibold text-[var(--pm-accent)]" href="{{ route('permission-manager.roles.edit', $role) }}">Edit</a>
                                <form method="POST" action="{{ route('permission-manager.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-semibold text-rose-600">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-[var(--pm-muted)]">No roles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $roles->links('permission-manager::partials._pagination') }}
@endsection
