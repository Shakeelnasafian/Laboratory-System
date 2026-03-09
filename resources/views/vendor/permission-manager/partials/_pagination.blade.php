@if ($paginator->hasPages())
    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[var(--pm-stroke)] bg-white/70 px-4 py-3 text-sm">
        <div class="text-[var(--pm-muted)]">
            Showing
            <span class="font-semibold text-[var(--pm-ink)]">{{ $paginator->firstItem() }}</span>
            to
            <span class="font-semibold text-[var(--pm-ink)]">{{ $paginator->lastItem() }}</span>
            of
            <span class="font-semibold text-[var(--pm-ink)]">{{ $paginator->total() }}</span>
        </div>
        <div class="flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <span class="rounded-full border border-[var(--pm-stroke)] px-3 py-1 text-[var(--pm-muted)]">Previous</span>
            @else
                <a class="rounded-full border border-[var(--pm-stroke)] px-3 py-1 hover:bg-[var(--pm-surface)]" href="{{ $paginator->previousPageUrl() }}">Previous</a>
            @endif

            @if ($paginator->hasMorePages())
                <a class="rounded-full border border-[var(--pm-stroke)] px-3 py-1 hover:bg-[var(--pm-surface)]" href="{{ $paginator->nextPageUrl() }}">Next</a>
            @else
                <span class="rounded-full border border-[var(--pm-stroke)] px-3 py-1 text-[var(--pm-muted)]">Next</span>
            @endif
        </div>
    </div>
@endif
