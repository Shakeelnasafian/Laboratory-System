@extends('permission-manager::layouts.app')

@section('title', 'Create Role')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold">Create Role</h1>
        <p class="mt-2 text-[var(--pm-muted)]">Define a new role with hierarchy and access flags.</p>
    </div>

    <div class="rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-6">
        <form method="POST" action="{{ route('permission-manager.roles.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="text-sm font-semibold">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" class="mt-2 w-full rounded-2xl border border-[var(--pm-stroke)] px-4 py-2 text-sm">
            </div>

            <div>
                <label class="text-sm font-semibold">Guard Name</label>
                <input type="text" name="guard_name" value="{{ old('guard_name', config('permission-manager.guard', 'web')) }}" class="mt-2 w-full rounded-2xl border border-[var(--pm-stroke)] px-4 py-2 text-sm">
            </div>

            <div>
                <label class="text-sm font-semibold">Description</label>
                <textarea name="description" rows="3" class="mt-2 w-full rounded-2xl border border-[var(--pm-stroke)] px-4 py-2 text-sm">{{ old('description') }}</textarea>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-semibold">Hierarchy Level</label>
                    <input type="number" name="hierarchy_level" value="{{ old('hierarchy_level', 0) }}" class="mt-2 w-full rounded-2xl border border-[var(--pm-stroke)] px-4 py-2 text-sm">
                </div>
                <div class="flex items-center gap-3 rounded-2xl border border-[var(--pm-stroke)] bg-[var(--pm-surface)] px-4 py-3">
                    <input type="checkbox" name="is_super_admin" value="1" {{ old('is_super_admin') ? 'checked' : '' }} class="h-4 w-4 rounded border-[var(--pm-stroke)]">
                    <label class="text-sm font-semibold">Super Admin</label>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-full bg-[var(--pm-accent)] px-5 py-2 text-sm font-semibold text-white">Create Role</button>
                <a href="{{ route('permission-manager.roles.index') }}" class="rounded-full border border-[var(--pm-stroke)] px-5 py-2 text-sm">Cancel</a>
            </div>
        </form>
    </div>
@endsection
