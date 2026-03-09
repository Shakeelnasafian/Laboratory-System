@extends('permission-manager::layouts.app')

@section('title', 'Role Details')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold">{{ $role->name }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-[var(--pm-muted)]">
                <span>Guard: {{ $role->guard_name }}</span>
                <span>Hierarchy: {{ $role->hierarchy_level }}</span>
                @if ($role->is_super_admin)
                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Super Admin</span>
                @endif
            </div>
            @if ($role->description)
                <p class="mt-3 text-[var(--pm-muted)]">{{ $role->description }}</p>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('permission-manager.roles.edit', $role) }}" class="rounded-full border border-[var(--pm-stroke)] px-5 py-2 text-sm">Edit Role</a>
            <a href="{{ route('permission-manager.roles.index') }}" class="rounded-full border border-[var(--pm-stroke)] px-5 py-2 text-sm">Back</a>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.3fr_1fr]">
        <div class="rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-6">
            <h2 class="text-lg font-semibold">Permissions</h2>
            <p class="mt-1 text-sm text-[var(--pm-muted)]">Assign permissions directly to this role.</p>

            <form method="POST" action="{{ route('permission-manager.roles.sync-permissions', $role) }}" class="mt-4 space-y-4">
                @csrf
                @foreach ($groupedPermissions as $group => $permissions)
                    <div class="rounded-2xl border border-[var(--pm-stroke)] bg-white/70 p-4">
                        <h3 class="text-sm font-semibold">{{ $group }}</h3>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($permissions as $permission)
                                <label class="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        name="permission_ids[]"
                                        value="{{ $permission->id }}"
                                        {{ in_array($permission->id, $rolePermissionIds, true) ? 'checked' : '' }}
                                        class="h-4 w-4 rounded border-[var(--pm-stroke)]"
                                    >
                                    <span>{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <button type="submit" class="rounded-full bg-[var(--pm-accent)] px-5 py-2 text-sm font-semibold text-white">Save Permissions</button>
            </form>
        </div>

        <div class="rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-6">
            <h2 class="text-lg font-semibold">Assigned Users</h2>
            <p class="mt-1 text-sm text-[var(--pm-muted)]">Users currently holding this role.</p>

            <div class="mt-4 space-y-3">
                @forelse ($users as $user)
                    <div class="rounded-2xl border border-[var(--pm-stroke)] bg-white/70 px-4 py-3">
                        <p class="text-sm font-semibold">{{ $user->{config('permission-manager.user_display_field', 'name')} }}</p>
                        @if (isset($user->email))
                            <p class="text-xs text-[var(--pm-muted)]">{{ $user->email }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-[var(--pm-muted)]">No users assigned yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
