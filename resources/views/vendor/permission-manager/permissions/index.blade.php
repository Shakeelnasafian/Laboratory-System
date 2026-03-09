@extends('permission-manager::layouts.app')

@section('title', 'Permissions')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold">Permissions</h1>
            <p class="mt-2 text-[var(--pm-muted)]">Group and maintain granular permission rules.</p>
        </div>
        <a href="{{ route('permission-manager.permissions.create') }}" class="rounded-full bg-[var(--pm-accent)] px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-200/50">Create Permission</a>
    </div>

    <div class="mt-6 rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-5">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search permissions..."
                class="w-full max-w-md rounded-2xl border border-[var(--pm-stroke)] bg-white px-4 py-2 text-sm focus:border-[var(--pm-accent)] focus:outline-none"
            >
            <select name="group" class="rounded-2xl border border-[var(--pm-stroke)] bg-white px-4 py-2 text-sm">
                <option value="">All groups</option>
                @foreach ($groups as $option)
                    <option value="{{ $option }}" {{ $group === $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
            <button class="rounded-full border border-[var(--pm-stroke)] px-4 py-2 text-sm hover:bg-[var(--pm-surface)]">Filter</button>
        </form>
    </div>

    <div class="mt-6 space-y-5">
        @forelse ($groupedPermissions as $groupName => $items)
            <div class="overflow-hidden rounded-3xl border border-[var(--pm-stroke)] bg-white/80">
                <div class="flex items-center justify-between border-b border-[var(--pm-stroke)] bg-[var(--pm-surface)] px-4 py-3">
                    <h2 class="text-sm font-semibold">{{ $groupName }}</h2>
                    <span class="text-xs text-[var(--pm-muted)]">{{ $items->count() }} permissions</span>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-[var(--pm-stroke)] text-xs uppercase tracking-wider text-[var(--pm-muted)]">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Guard</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $permission)
                            <tr class="border-b border-[var(--pm-stroke)] last:border-b-0">
                                <td class="px-4 py-3 font-medium">{{ $permission->name }}</td>
                                <td class="px-4 py-3 text-[var(--pm-muted)]">{{ $permission->guard_name }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <a class="text-sm font-semibold text-[var(--pm-accent)]" href="{{ route('permission-manager.permissions.edit', $permission) }}">Edit</a>
                                        <form method="POST" action="{{ route('permission-manager.permissions.destroy', $permission) }}" onsubmit="return confirm('Delete this permission?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-semibold text-rose-600">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="rounded-3xl border border-[var(--pm-stroke)] bg-white/80 px-4 py-6 text-center text-[var(--pm-muted)]">
                No permissions found.
            </div>
        @endforelse
    </div>

    {{ $permissions->links('permission-manager::partials._pagination') }}
@endsection
