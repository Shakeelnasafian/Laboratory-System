<div class="max-w-6xl mx-auto space-y-6" x-data="{ confirmCancel: false }">
    {{-- Cancel confirmation modal --}}
    <div x-show="confirmCancel" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" x-transition>
        <div class="bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Cancel Order?</h3>
            <p class="text-sm text-gray-600 mb-5">This will mark order <span class="font-mono font-medium">{{ $order->order_number }}</span> as cancelled. This action cannot be undone.</p>
            <div class="flex gap-3 justify-end">
                <button @click="confirmCancel = false" class="border px-4 py-2 rounded-lg text-sm text-gray-700 hover:bg-gray-100">Keep Order</button>
                <button wire:click="cancelOrder" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700">
                    <span wire:loading.remove wire:target="cancelOrder">Yes, Cancel</span>
                    <span wire:loading wire:target="cancelOrder">Cancelling...</span>
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Order #{{ $order->order_number }}</h2>
                <p class="text-sm text-gray-500 mt-1">Placed {{ $order->created_at->format('d M Y, h:i A') }} by {{ $order->createdBy->name }}</p>
                @if($order->is_urgent)
                    <div class="mt-3"><x-status-badge type="signal" status="urgent" /></div>
                @endif
            </div>
            <div class="flex flex-wrap gap-2 justify-end">
                @if($order->canPrintReport())
                    <a href="{{ route('lab.orders.report', $order) }}" target="_blank" class="app-btn-success rounded-lg px-4 py-2 text-sm">Print Report</a>
                @else
                    <span class="px-4 py-2 rounded-lg text-sm bg-yellow-50 text-yellow-800">Release report after all results are verified.</span>
                @endif
                <a href="{{ route('lab.samples.collection') }}" wire:navigate class="border px-4 py-2 rounded-lg text-sm">Samples</a>
                <a href="{{ route('lab.worklists.index') }}" wire:navigate class="border px-4 py-2 rounded-lg text-sm">Worklists</a>
                <a href="{{ route('lab.results.release') }}" wire:navigate class="border px-4 py-2 rounded-lg text-sm">Release Queue</a>
                @if(in_array($order->status, [\App\Models\Order::STATUS_PENDING, \App\Models\Order::STATUS_SAMPLE_COLLECTED]))
                    <button @click="confirmCancel = true" class="border border-red-300 text-red-600 px-4 py-2 rounded-lg text-sm hover:bg-red-50">Cancel Order</button>
                @endif
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-3 text-sm">
            <x-status-badge type="order" :status="$order->status" />
            @if($order->canPrintReport())
                <x-status-badge type="queue" status="released" label="Report Released" />
            @elseif($order->canReleaseReport())
                <x-status-badge type="queue" status="ready" label="Ready for Release" />
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-semibold text-gray-800 mb-3">Patient Information</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div><p class="text-gray-500">Name</p><p class="font-medium text-gray-800">{{ $order->patient->name }}</p></div>
            <div><p class="text-gray-500">ID</p><p class="font-mono text-gray-700">{{ $order->patient->patient_id }}</p></div>
            <div><p class="text-gray-500">Age / Gender</p><p class="text-gray-700">{{ $order->patient->age ?? 'N/A' }} {{ $order->patient->age_unit }} / {{ ucfirst($order->patient->gender) }}</p></div>
            <div><p class="text-gray-500">Phone</p><p class="text-gray-700">{{ $order->patient->phone ?? 'N/A' }}</p></div>
            @if($order->referred_by)
                <div><p class="text-gray-500">Referred By</p><p class="text-gray-700">{{ $order->referred_by }}</p></div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-800">Workflow by Test</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-6 py-3 text-left">Test</th>
                    <th class="px-6 py-3 text-left">Sample</th>
                    <th class="px-6 py-3 text-left">Bench</th>
                    <th class="px-6 py-3 text-left">Result</th>
                    <th class="px-6 py-3 text-left">Release</th>
                    <th class="px-6 py-3 text-right">Price</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($order->items as $item)
                    <tr>
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $item->test->name }}</td>
                        <td class="px-6 py-4">
                            @if($item->sample)
                                <div class="font-mono text-gray-700">{{ $item->sample->accession_number }}</div>
                                <div class="text-xs text-gray-500">{{ ucfirst($item->sample->status) }} · {{ $item->sample->sample_type }}</div>
                            @else
                                <span class="text-gray-400">Awaiting collection</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-gray-700">{{ $item->assignedTo?->name ?: 'Unassigned' }}</div>
                            <div class="text-xs text-gray-500">{{ str_replace('_', ' ', ucfirst($item->status)) }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($item->result)
                                <div class="{{ $item->result->is_abnormal ? 'text-red-600 font-semibold' : 'text-gray-800' }}">{{ $item->result->value }} {{ $item->result->unit }}</div>
                                <div class="text-xs text-gray-500">{{ $item->result->normal_range ?: 'No range' }}</div>
                            @else
                                <span class="text-gray-400">Pending</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($item->result)
                                <x-status-badge type="result" :status="$item->result->status" />
                                @if($item->result->flag === 'critical')
                                    <div class="mt-1"><x-status-badge type="signal" status="critical" /></div>
                                @endif
                            @else
                                <span class="text-gray-400">Not started</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-gray-700">Rs. {{ number_format($item->price) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 font-medium">
                <tr>
                    <td colspan="5" class="px-6 py-2 text-right text-gray-600">Subtotal:</td>
                    <td class="px-6 py-2 text-right text-gray-800">Rs. {{ number_format($order->total_amount) }}</td>
                </tr>
                @if($order->discount > 0)
                    <tr>
                        <td colspan="5" class="px-6 py-2 text-right text-gray-600">Discount:</td>
                        <td class="px-6 py-2 text-right text-red-500">-Rs. {{ number_format($order->discount) }}</td>
                    </tr>
                @endif
                <tr class="text-lg">
                    <td colspan="5" class="px-6 py-2 text-right font-bold text-gray-800">Net Total:</td>
                    <td class="px-6 py-2 text-right font-bold text-green-600">Rs. {{ number_format($order->net_amount) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($order->invoice)
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-semibold text-gray-800 mb-3">Payment</h3>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                <div><p class="text-gray-500">Invoice #</p><p class="font-mono text-gray-700">{{ $order->invoice->invoice_number }}</p></div>
                <div><p class="text-gray-500">Total</p><p class="font-medium text-gray-800">Rs. {{ number_format($order->invoice->total) }}</p></div>
                <div><p class="text-gray-500">Paid</p><p class="font-medium text-green-600">Rs. {{ number_format($order->invoice->paid_amount) }}</p></div>
                <div><p class="text-gray-500">Balance</p><p class="font-medium {{ $order->invoice->balance > 0 ? 'text-red-600' : 'text-gray-500' }}">Rs. {{ number_format($order->invoice->balance) }}</p></div>
                <div><p class="text-gray-500">Status</p><x-status-badge type="payment" :status="$order->invoice->payment_status" /></div>
            </div>
        </div>
    @endif

    <div class="flex justify-start">
        <a href="{{ route('lab.orders.index') }}" wire:navigate class="app-link-primary text-sm">Back to Orders</a>
    </div>
</div>
