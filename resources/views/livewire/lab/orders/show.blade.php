@use('App\Models\Order')
@php
    $invoice = $order->invoice;
    $hasBalance = $invoice && $invoice->balance > 0;
@endphp

<div class="mx-auto max-w-5xl space-y-5" x-data="{ confirmCancel: false }">
    {{-- Cancel confirmation modal --}}
    <div x-show="confirmCancel" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" x-transition>
        <div class="mx-4 w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
            <h3 class="mb-2 text-lg font-semibold text-gray-800">Cancel Order?</h3>
            <p class="mb-5 text-sm text-gray-600">This will mark order <span class="font-mono font-medium">{{ $order->order_number }}</span> as cancelled. This action cannot be undone.</p>
            <div class="flex justify-end gap-3">
                <button @click="confirmCancel = false" class="rounded-lg border px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Keep Order</button>
                <button wire:click="cancelOrder" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                    <span wire:loading.remove wire:target="cancelOrder">Yes, Cancel</span>
                    <span wire:loading wire:target="cancelOrder">Cancelling...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Order header --}}
    <div class="rounded-xl bg-white p-5 shadow">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-bold text-gray-800">Order #{{ $order->order_number }}</h2>
                    @if($order->is_urgent)
                        <x-status-badge type="signal" status="urgent" />
                    @endif
                    <x-status-badge type="order" :status="$order->status" />
                </div>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $order->patient->name }} · {{ $order->patient->age ?? 'N/A' }} {{ $order->patient->age_unit }} · {{ ucfirst($order->patient->gender) }}
                    · <span class="font-mono">{{ $order->patient->patient_id }}</span>
                    @if($order->patient->phone) · {{ $order->patient->phone }} @endif
                </p>
                <p class="mt-1 text-xs text-gray-500">
                    Placed {{ $order->created_at->format('d M Y, h:i A') }} by {{ $order->createdBy->name }}
                    @if($order->referred_by) · Ref: {{ $order->referred_by }} @endif
                </p>
            </div>

            {{-- Lab status: the work itself happens on the sample page --}}
            <div class="rounded-lg border bg-gray-50 px-4 py-3 text-right">
                <p class="text-xs text-gray-500">Lab status</p>
                <p class="font-semibold text-gray-800">{{ Order::STEP_LABELS[$step] }}</p>
                @if($step !== Order::STEP_CANCELLED)
                    <div class="mt-2 flex justify-end gap-2">
                        @if($step === Order::STEP_DONE)
                            <a href="{{ route('lab.orders.report', $order) }}" target="_blank" class="app-btn-success rounded-lg px-3 py-1.5 text-xs font-semibold">Print report</a>
                        @endif
                        <a href="{{ route('lab.samples.show', $order) }}" wire:navigate class="app-btn-primary rounded-lg px-3 py-1.5 text-xs font-semibold">Open sample</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($notice)
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ $notice }}</div>
    @endif

    {{-- Tests and prices --}}
    <div class="overflow-hidden rounded-xl bg-white shadow">
        <div class="border-b px-5 py-3">
            <h3 class="font-semibold text-gray-800">Tests</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-5 py-2 text-left">Test</th>
                    <th class="px-5 py-2 text-left">Sample type</th>
                    <th class="px-5 py-2 text-right">Price</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($order->items as $item)
                    <tr wire:key="price-{{ $item->id }}">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ $item->test->name }} <span class="text-xs text-gray-400">{{ $item->test->code }}</span></td>
                        <td class="px-5 py-3 text-gray-600">{{ $item->sample?->sample_type ?: ($item->test->sample_type ?: '—') }}</td>
                        <td class="px-5 py-3 text-right text-gray-700">Rs. {{ number_format($item->price) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 font-medium">
                <tr>
                    <td colspan="2" class="px-5 py-2 text-right text-gray-600">Subtotal:</td>
                    <td class="px-5 py-2 text-right text-gray-800">Rs. {{ number_format($order->total_amount) }}</td>
                </tr>
                @if($order->discount > 0)
                    <tr>
                        <td colspan="2" class="px-5 py-2 text-right text-gray-600">Discount:</td>
                        <td class="px-5 py-2 text-right text-red-500">-Rs. {{ number_format($order->discount) }}</td>
                    </tr>
                @endif
                <tr class="text-lg">
                    <td colspan="2" class="px-5 py-2 text-right font-bold text-gray-800">Net Total:</td>
                    <td class="px-5 py-2 text-right font-bold text-green-600">Rs. {{ number_format($order->net_amount) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Payment --}}
    @if($invoice)
        <div class="rounded-xl bg-white p-5 shadow">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-semibold text-gray-800">Payment</h3>
                @if($hasBalance && $order->status !== Order::STATUS_CANCELLED)
                    <button wire:click="receiveBalance" wire:confirm="Receive Rs. {{ number_format($invoice->balance) }} and mark the invoice as paid?" class="app-btn-success rounded-lg px-4 py-2 text-sm font-semibold">
                        Receive balance Rs. {{ number_format($invoice->balance) }}
                    </button>
                @endif
            </div>
            <div class="mt-3 grid grid-cols-2 gap-4 text-sm md:grid-cols-5">
                <div><p class="text-gray-500">Invoice #</p><p class="font-mono text-gray-700">{{ $invoice->invoice_number }}</p></div>
                <div><p class="text-gray-500">Total</p><p class="font-medium text-gray-800">Rs. {{ number_format($invoice->total) }}</p></div>
                <div><p class="text-gray-500">Paid</p><p class="font-medium text-green-600">Rs. {{ number_format($invoice->paid_amount) }}</p></div>
                <div><p class="text-gray-500">Balance</p><p class="font-medium {{ $hasBalance ? 'text-red-600' : 'text-gray-500' }}">Rs. {{ number_format($invoice->balance) }}</p></div>
                <div><p class="text-gray-500">Status</p><x-status-badge type="payment" :status="$invoice->payment_status" /></div>
            </div>
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('lab.orders.index') }}" wire:navigate class="app-link-primary text-sm">&larr; Back to orders</a>
        @if(in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_SAMPLE_COLLECTED]))
            <button @click="confirmCancel = true" class="rounded-lg border border-red-300 px-4 py-2 text-sm text-red-600 hover:bg-red-50">Cancel order</button>
        @endif
    </div>
</div>
