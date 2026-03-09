@extends('permission-manager::layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-semibold">Dashboard</h1>
        <p class="mt-2 text-[var(--pm-muted)]">Snapshot of roles, permissions, and recent activity.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-5">
            <p class="text-sm text-[var(--pm-muted)]">Roles</p>
            <p class="mt-3 text-3xl font-semibold">{{ $totalRoles }}</p>
        </div>
        <div class="rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-5">
            <p class="text-sm text-[var(--pm-muted)]">Permissions</p>
            <p class="mt-3 text-3xl font-semibold">{{ $totalPermissions }}</p>
        </div>
        <div class="rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-5">
            <p class="text-sm text-[var(--pm-muted)]">Users</p>
            <p class="mt-3 text-3xl font-semibold">{{ $totalUsers }}</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.1fr_1fr]">
        <div class="rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-6">
            <h2 class="text-lg font-semibold">Permissions by Group</h2>
            <div class="mt-4 flex flex-wrap gap-3">
                @forelse ($groupCounts as $group)
                    <span class="rounded-full border border-[var(--pm-stroke)] bg-[var(--pm-surface)] px-3 py-1 text-sm">
                        {{ $group->group ?: 'Ungrouped' }}
                        <span class="ml-2 text-[var(--pm-muted)]">{{ $group->total }}</span>
                    </span>
                @empty
                    <p class="text-sm text-[var(--pm-muted)]">No permission groups found yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-6">
            <h2 class="text-lg font-semibold">Recent Audit Activity</h2>
            <div class="mt-4 space-y-4">
                @if (! empty($auditLogs) && count($auditLogs) > 0)
                    @foreach ($auditLogs as $log)
                        <div class="rounded-2xl border border-[var(--pm-stroke)] bg-white/70 px-4 py-3">
                            <p class="text-sm font-medium">{{ ucfirst($log->action) }} {{ $log->entity_type }} {{ $log->entity_name }}</p>
                            <p class="mt-1 text-xs text-[var(--pm-muted)]">
                                {{ optional($log->created_at)->format('M d, Y H:i') }}
                                @if ($log->user_id)
                                    - User #{{ $log->user_id }}
                                @endif
                            </p>
                        </div>
                    @endforeach
                @else
                    <p class="text-sm text-[var(--pm-muted)]">Audit log is empty or disabled.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
