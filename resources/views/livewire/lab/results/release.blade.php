<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex flex-wrap gap-3">
            <input wire:model.live.debounce.500ms="search" type="text" placeholder="Search order or patient..." class="border rounded-lg px-4 py-2 text-sm w-64">
            <select wire:model.live="queue" class="border rounded-lg px-3 py-2 text-sm">
                <option value="ready">Ready to Release</option>
                <option value="released">Released</option>
                <option value="critical">Critical Queue</option>
            </select>
        </div>
        <a href="{{ route('lab.results.index') }}" wire:navigate class="app-link-primary text-sm">Back to result entry</a>
    </div>

    <div class="space-y-4">
        @forelse($orders as $order)
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('lab.samples.show', $order) }}" wire:navigate class="app-link-primary text-lg font-semibold">{{ $order->order_number }}</a>
                            @if($order->critical_item_count > 0)
                                <x-status-badge type="signal" status="critical" :label="'Critical ' . $order->critical_item_count" />
                            @endif
                        </div>
                        <div class="text-sm text-gray-600 mt-1">{{ $order->patient->name }} · {{ $order->item_count }} test(s)</div>
                        <div class="text-xs text-gray-500 mt-1">Verified {{ $order->ready_item_count }}/{{ $order->item_count }} · Released {{ $order->released_item_count }}/{{ $order->item_count }}</div>
                    </div>
                    <div class="text-right">
                        @if($order->canPrintReport())
                            <x-status-badge type="queue" status="released" class="mb-3" />
                        @elseif($order->canReleaseReport())
                            <x-status-badge type="queue" status="ready" label="Ready to Release" class="mb-3" />
                        @else
                            <x-status-badge type="queue" status="pending_verification" class="mb-3" />
                        @endif
                        <div>
                            @if($order->canReleaseReport() && ! $order->canPrintReport())
                                <button wire:click="release({{ $order->id }})" class="app-btn-primary rounded-lg px-4 py-2 text-sm">Release Report</button>
                            @elseif($order->canPrintReport())
                                <a href="{{ route('lab.orders.report', $order) }}" target="_blank" class="app-btn-success rounded-lg px-4 py-2 text-sm inline-flex">Print Report</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow p-10 text-center text-gray-400">No orders match the release queue.</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
</div>
