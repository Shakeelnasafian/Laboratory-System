<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex flex-wrap gap-3">
            <input wire:model.live="search" type="text" placeholder="Search order or patient..." class="border rounded-lg px-4 py-2 text-sm w-64">
            <select wire:model.live="queue" class="border rounded-lg px-3 py-2 text-sm">
                <option value="ready">Ready to Release</option>
                <option value="released">Released</option>
                <option value="critical">Critical Queue</option>
            </select>
        </div>
        <a href="{{ route('lab.results.index') }}" class="text-sm text-blue-600 hover:underline">Back to result entry</a>
    </div>

    <div class="space-y-4">
        @forelse($orders as $order)
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('lab.orders.show', $order) }}" class="text-lg font-semibold text-blue-600 hover:underline">{{ $order->order_number }}</a>
                            @if($order->critical_item_count > 0)
                                <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">Critical {{ $order->critical_item_count }}</span>
                            @endif
                        </div>
                        <div class="text-sm text-gray-600 mt-1">{{ $order->patient->name }} · {{ $order->item_count }} test(s)</div>
                        <div class="text-xs text-gray-500 mt-1">Verified {{ $order->ready_item_count }}/{{ $order->item_count }} · Released {{ $order->released_item_count }}/{{ $order->item_count }}</div>
                    </div>
                    <div class="text-right">
                        @if($order->canPrintReport())
                            <span class="inline-flex px-3 py-1 rounded-full text-xs bg-green-100 text-green-700 mb-3">Released</span>
                        @elseif($order->canReleaseReport())
                            <span class="inline-flex px-3 py-1 rounded-full text-xs bg-blue-100 text-blue-700 mb-3">Ready to release</span>
                        @else
                            <span class="inline-flex px-3 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700 mb-3">Pending verification</span>
                        @endif
                        <div>
                            @if($order->canReleaseReport() && ! $order->canPrintReport())
                                <button wire:click="release({{ $order->id }})" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">Release Report</button>
                            @elseif($order->canPrintReport())
                                <a href="{{ route('lab.orders.report', $order) }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm inline-flex">Print Report</a>
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