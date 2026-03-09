@extends('permission-manager::layouts.app')

@section('title', 'Audit Log')

@section('content')
    <div>
        <h1 class="text-3xl font-semibold">Audit Log</h1>
        <p class="mt-2 text-[var(--pm-muted)]">Track changes to roles, permissions, and assignments.</p>
    </div>

    <div class="mt-6 rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-5">
        <form method="GET" class="grid gap-3 md:grid-cols-5">
            <select name="entity_type" class="rounded-2xl border border-[var(--pm-stroke)] bg-white px-4 py-2 text-sm">
                <option value="">Entity type</option>
                @foreach (['role', 'permission', 'user_role', 'user_permission'] as $type)
                    <option value="{{ $type }}" {{ ($filters['entity_type'] ?? '') === $type ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>

            <select name="action" class="rounded-2xl border border-[var(--pm-stroke)] bg-white px-4 py-2 text-sm">
                <option value="">Action</option>
                @foreach (['created', 'updated', 'deleted', 'assigned', 'revoked'] as $action)
                    <option value="{{ $action }}" {{ ($filters['action'] ?? '') === $action ? 'selected' : '' }}>{{ ucfirst($action) }}</option>
                @endforeach
            </select>

            <select name="user_id" class="rounded-2xl border border-[var(--pm-stroke)] bg-white px-4 py-2 text-sm">
                <option value="">All users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ (string) ($filters['user_id'] ?? '') === (string) $user->id ? 'selected' : '' }}>
                        {{ $user->{config('permission-manager.user_display_field', 'name')} }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="rounded-2xl border border-[var(--pm-stroke)] bg-white px-4 py-2 text-sm">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="rounded-2xl border border-[var(--pm-stroke)] bg-white px-4 py-2 text-sm">

            <div class="md:col-span-5 flex flex-wrap gap-3">
                <button class="rounded-full border border-[var(--pm-stroke)] px-4 py-2 text-sm hover:bg-[var(--pm-surface)]">Apply Filters</button>
                <a href="{{ route('permission-manager.audit.index') }}" class="rounded-full border border-[var(--pm-stroke)] px-4 py-2 text-sm">Reset</a>
            </div>
        </form>
    </div>

    <div class="mt-6 overflow-hidden rounded-3xl border border-[var(--pm-stroke)] bg-white/80">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-[var(--pm-stroke)] bg-[var(--pm-surface)] text-xs uppercase tracking-wider text-[var(--pm-muted)]">
                <tr>
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Entity</th>
                    <th class="px-4 py-3">User</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($auditLogs as $log)
                    <tr class="border-b border-[var(--pm-stroke)] last:border-b-0">
                        <td class="px-4 py-3 text-[var(--pm-muted)]">{{ optional($log->created_at)->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-3 font-medium">{{ ucfirst($log->action) }}</td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium">{{ ucfirst($log->entity_type) }}</div>
                            <div class="text-xs text-[var(--pm-muted)]">{{ $log->entity_name ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-[var(--pm-muted)]">{{ $log->user_id ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-[var(--pm-muted)]">No audit entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $auditLogs->links('permission-manager::partials._pagination') }}
@endsection
