<div>
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-4">
        @foreach($statCards as $card)
            <article
                class="dashboard-metric-card group"
                style="--metric-accent: {{ $card['accent'] }}; --metric-soft: {{ $card['soft'] }};"
            >
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="dashboard-metric-label">{{ $card['label'] }}</p>
                            <span class="dashboard-metric-pill">{{ $card['trendLabel'] }}</span>
                        </div>
                        <p class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-[2rem]">{{ $card['value'] }}</p>
                        <p class="mt-2 max-w-xs text-sm leading-6 text-slate-500">{{ $card['subtitle'] }}</p>
                    </div>

                    <div class="dashboard-metric-chart">
                        <svg viewBox="0 0 148 56" class="h-16 w-full" aria-hidden="true">
                            <defs>
                                <linearGradient id="metric-area-{{ $loop->index }}" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="var(--metric-accent)" stop-opacity="0.35" />
                                    <stop offset="100%" stop-color="var(--metric-accent)" stop-opacity="0.02" />
                                </linearGradient>
                            </defs>
                            <path d="M4 46 H144" fill="none" stroke="rgba(148, 163, 184, 0.18)" stroke-width="1" stroke-dasharray="4 4" />
                            <path d="M4 18 H144" fill="none" stroke="rgba(148, 163, 184, 0.10)" stroke-width="1" stroke-dasharray="4 4" />
                            <path d="{{ $card['spark']['area'] }}" fill="url(#metric-area-{{ $loop->index }})" />
                            <path d="{{ $card['spark']['path'] }}" fill="none" stroke="var(--metric-accent)" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" />
                            <circle cx="{{ $card['spark']['lastX'] }}" cy="{{ $card['spark']['lastY'] }}" r="4.5" fill="white" stroke="var(--metric-accent)" stroke-width="2" />
                            <circle cx="{{ $card['spark']['lastX'] }}" cy="{{ $card['spark']['lastY'] }}" r="8" fill="var(--metric-accent)" opacity="0.10" />
                        </svg>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mb-6 flex flex-wrap gap-3">
        <a href="{{ route('lab.orders.create') }}" wire:navigate class="rounded-2xl bg-blue-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-blue-700">New Order</a>
        <a href="{{ route('lab.samples.collection') }}" wire:navigate class="rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50">Samples</a>
        <a href="{{ route('lab.worklists.index') }}" wire:navigate class="rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50">Worklists</a>
        <a href="{{ route('lab.results.release') }}" wire:navigate class="rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50">Release Queue</a>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
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
                            <td class="whitespace-nowrap px-6 py-4 align-top"><a href="{{ route('lab.orders.show', $order) }}" wire:navigate class="font-medium text-blue-600 hover:underline">{{ $order->order_number }}</a></td>
                            <td class="px-6 py-4 text-slate-700">{{ $order->patient->name }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-500">{{ $order->items->count() }} test(s)</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @php($colors = ['pending' => 'yellow', 'sample_collected' => 'blue', 'processing' => 'indigo', 'completed' => 'green', 'cancelled' => 'red'])
                                @php($color = $colors[$order->status] ?? 'gray')
                                <span class="rounded-full px-2 py-1 text-xs bg-{{ $color }}-100 text-{{ $color }}-700">{{ \App\Models\Order::STATUSES[$order->status] }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($order->canPrintReport())
                                    <span class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-700">Released</span>
                                @elseif($order->canReleaseReport())
                                    <span class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-700">Ready</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-600">In progress</span>
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
