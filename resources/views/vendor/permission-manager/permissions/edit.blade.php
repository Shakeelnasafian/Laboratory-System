@extends('permission-manager::layouts.app')

@section('title', 'Edit Permission')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-semibold">Edit Permission</h1>
        <p class="mt-2 text-[var(--pm-muted)]">Update permission metadata and grouping.</p>
    </div>

    <div class="rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-6">
        <form method="POST" action="{{ route('permission-manager.permissions.update', $permission) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="text-sm font-semibold">Name</label>
                <input type="text" name="name" value="{{ old('name', $permission->name) }}" class="mt-2 w-full rounded-2xl border border-[var(--pm-stroke)] px-4 py-2 text-sm">
            </div>

            <div>
                <label class="text-sm font-semibold">Guard Name</label>
                <input type="text" name="guard_name" value="{{ old('guard_name', $permission->guard_name) }}" class="mt-2 w-full rounded-2xl border border-[var(--pm-stroke)] px-4 py-2 text-sm">
            </div>

            <div>
                <label class="text-sm font-semibold">Group</label>
                <input type="text" name="group" value="{{ old('group', $permission->group) }}" list="permission-groups" class="mt-2 w-full rounded-2xl border border-[var(--pm-stroke)] px-4 py-2 text-sm">
                <datalist id="permission-groups">
                    @foreach ($groups as $group)
                        <option value="{{ $group }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-full bg-[var(--pm-accent)] px-5 py-2 text-sm font-semibold text-white">Save Changes</button>
                <a href="{{ route('permission-manager.permissions.index') }}" class="rounded-full border border-[var(--pm-stroke)] px-5 py-2 text-sm">Cancel</a>
            </div>
        </form>
    </div>
@endsection
