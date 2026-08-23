<div>
    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach($statCards as $card)
            <article
                class="dashboard-metric-card group"
                style="--metric-accent: {{ $card['accent'] }}; --metric-soft: {{ $card['soft'] }};"
            >
                <div class="dashboard-metric-content">
                    <div class="dashboard-metric-header">
                        <p class="dashboard-metric-label">{{ $card['label'] }}</p>
                        <span class="dashboard-metric-pill">{{ $card['trendLabel'] }}</span>
                    </div>

                    <div class="dashboard-metric-body">
                        <p class="dashboard-metric-value">{{ $card['value'] }}</p>
                        <p class="dashboard-metric-subtitle">{{ $card['subtitle'] }}</p>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mb-6 flex flex-wrap gap-3">
        <a href="{{ route('lab.orders.create') }}" wire:navigate class="app-btn-primary rounded-2xl px-5 py-2.5 text-sm font-medium shadow-sm transition hover:-translate-y-0.5">New Order</a>
        <a href="{{ route('lab.samples.collection') }}" wire:navigate class="rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50">Samples</a>
        <a href="{{ route('lab.worklists.index') }}" wire:navigate class="rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50">Worklists</a>
        <a href="{{ route('lab.results.release') }}" wire:navigate class="rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50">Release Queue</a>
    </div>

    <div class="app-surface-card overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-white">
        <div class="border-b border-slate-200/80 px-6 py-4">
            <h2 class="font-semibold text-slate-800">Recent Orders</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-6 py-3 text-left">Order</th>
                        <th class="px-6 py-3 text-left">Patient</th>
                        <th class="px-6 py-3 text-left">Tests</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Release</th>
                        <th class="px-6 py-3 text-left">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentOrders as $order)
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 align-top"><a href="{{ route('lab.orders.show', $order) }}" wire:navigate class="app-link-primary font-medium">{{ $order->order_number }}</a></td>
                            <td class="px-6 py-4 text-slate-700">{{ $order->patient->name }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-500">{{ $order->items->count() }} test(s)</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <x-status-badge type="order" :status="$order->status" />
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($order->canPrintReport())
                                    <x-status-badge type="queue" status="released" />
                                @elseif($order->canReleaseReport())
                                    <x-status-badge type="queue" status="ready" />
                                @else
                                    <x-status-badge type="queue" status="in_progress" />
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-400">{{ $order->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-8 text-center text-slate-400">No recent orders.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
