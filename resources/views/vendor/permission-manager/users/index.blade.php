@extends('permission-manager::layouts.app')

@section('title', 'Users')

@section('content')
    <div>
        <h1 class="text-3xl font-semibold">Users</h1>
        <p class="mt-2 text-[var(--pm-muted)]">Assign roles and direct permissions to users.</p>
    </div>

    <div class="mt-6 rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-5">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search users..."
                class="w-full max-w-md rounded-2xl border border-[var(--pm-stroke)] bg-white px-4 py-2 text-sm focus:border-[var(--pm-accent)] focus:outline-none"
            >
            <button class="rounded-full border border-[var(--pm-stroke)] px-4 py-2 text-sm hover:bg-[var(--pm-surface)]">Search</button>
        </form>
    </div>

    <div class="mt-6 overflow-hidden rounded-3xl border border-[var(--pm-stroke)] bg-white/80">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-[var(--pm-stroke)] bg-[var(--pm-surface)] text-xs uppercase tracking-wider text-[var(--pm-muted)]">
                <tr>
                    <th class="px-4 py-3">{{ ucfirst($displayField) }}</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="border-b border-[var(--pm-stroke)] last:border-b-0">
                        <td class="px-4 py-3 font-medium">{{ $user->{$displayField} }}</td>
                        <td class="px-4 py-3 text-[var(--pm-muted)]">{{ $user->email ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <a class="text-sm font-semibold text-[var(--pm-accent)]" href="{{ route('permission-manager.users.show', $user) }}">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-[var(--pm-muted)]">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links('permission-manager::partials._pagination') }}
@endsection
