@extends('permission-manager::layouts.app')

@section('title', 'Import / Export')

@section('content')
    <div>
        <h1 class="text-3xl font-semibold">Import / Export</h1>
        <p class="mt-2 text-[var(--pm-muted)]">Move role and permission data between environments.</p>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-6">
            <h2 class="text-lg font-semibold">Export Data</h2>
            <p class="mt-1 text-sm text-[var(--pm-muted)]">Download roles and permissions as JSON or CSV.</p>

            <form method="POST" action="{{ route('permission-manager.import-export.export') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="text-sm font-semibold">Format</label>
                    <select name="format" class="mt-2 w-full rounded-2xl border border-[var(--pm-stroke)] bg-white px-4 py-2 text-sm">
                        <option value="json">JSON</option>
                        <option value="csv">CSV</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm font-semibold">Include</label>
                    <select name="include" class="mt-2 w-full rounded-2xl border border-[var(--pm-stroke)] bg-white px-4 py-2 text-sm">
                        <option value="both">Roles + Permissions</option>
                        <option value="roles">Roles only</option>
                        <option value="permissions">Permissions only</option>
                    </select>
                </div>

                <button type="submit" class="rounded-full bg-[var(--pm-accent)] px-5 py-2 text-sm font-semibold text-white">Download Export</button>
            </form>
        </div>

        <div class="rounded-3xl border border-[var(--pm-stroke)] bg-white/80 p-6">
            <h2 class="text-lg font-semibold">Import Data</h2>
            <p class="mt-1 text-sm text-[var(--pm-muted)]">Upload a JSON or CSV export to sync.</p>

            <form method="POST" action="{{ route('permission-manager.import-export.import') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="text-sm font-semibold">File</label>
                    <input type="file" name="file" class="mt-2 w-full rounded-2xl border border-[var(--pm-stroke)] bg-white px-4 py-2 text-sm">
                </div>

                <div>
                    <label class="text-sm font-semibold">Format (optional)</label>
                    <select name="format" class="mt-2 w-full rounded-2xl border border-[var(--pm-stroke)] bg-white px-4 py-2 text-sm">
                        <option value="">Auto-detect</option>
                        <option value="json">JSON</option>
                        <option value="csv">CSV</option>
                    </select>
                </div>

                <button type="submit" class="rounded-full bg-[var(--pm-accent-2)] px-5 py-2 text-sm font-semibold text-white">Import File</button>
            </form>
        </div>
    </div>
@endsection
