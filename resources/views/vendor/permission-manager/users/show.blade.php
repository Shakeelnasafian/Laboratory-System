@extends('permission-manager::layouts.app')

@section('title', 'User Permissions')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold">{{ $user->{$displayField} }}</h1>
            <p class="mt-2 text-[var(--pm-muted)]">Manage roles and direct permissions.</p>
            @if (! empty($user->email))
                <p class="mt-1 text-sm text-[var(--pm-muted)]">{{ $user->email }}</p>
            @endif
        </div>
        <a href="{{ route('permission-manager.users.index') }}" class="rounded-full border border-[var(--pm-stroke)] px-5 py-2 text-sm">Back to Users</a>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-6">
            <h2 class="text-lg font-semibold">Assign Roles</h2>
            <p class="mt-1 text-sm text-[var(--pm-muted)]">Roles control bundled permissions.</p>

            <form method="POST" action="{{ route('permission-manager.users.sync-roles', $user) }}" class="mt-4 space-y-4">
                @csrf
                @foreach ($roles as $level => $roleGroup)
                    <div class="rounded-2xl border border-[var(--pm-stroke)] bg-white/70 p-4">
                        <h3 class="text-sm font-semibold">Hierarchy Level {{ $level }}</h3>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($roleGroup as $role)
                                <label class="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        name="role_ids[]"
                                        value="{{ $role->id }}"
                                        {{ in_array($role->id, $userRoleIds, true) ? 'checked' : '' }}
                                        class="h-4 w-4 rounded border-[var(--pm-stroke)]"
                                    >
                                    <span>{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <button type="submit" class="rounded-full bg-[var(--pm-accent)] px-5 py-2 text-sm font-semibold text-white">Save Roles</button>
            </form>
        </div>

        <div class="rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-6">
            <h2 class="text-lg font-semibold">Direct Permissions</h2>
            <p class="mt-1 text-sm text-[var(--pm-muted)]">Overrides and exceptions for this user.</p>

            <form method="POST" action="{{ route('permission-manager.users.sync-permissions', $user) }}" class="mt-4 space-y-4">
                @csrf
                @foreach ($permissions as $group => $permissionGroup)
                    <div class="rounded-2xl border border-[var(--pm-stroke)] bg-white/70 p-4">
                        <h3 class="text-sm font-semibold">{{ $group }}</h3>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($permissionGroup as $permission)
                                <label class="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        name="permission_ids[]"
                                        value="{{ $permission->id }}"
                                        {{ in_array($permission->id, $userPermissionIds, true) ? 'checked' : '' }}
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
    </div>
@endsection
