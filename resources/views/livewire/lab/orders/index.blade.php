@use('App\Models\Order')
<div>
    <div class="mb-6 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="grid gap-3 sm:grid-cols-2 xl:flex xl:flex-wrap">
            <input wire:model.live.debounce.500ms="search" type="text" placeholder="Order # or patient name..." class="w-full rounded-lg border px-4 py-2 text-sm sm:min-w-80 xl:w-80">
            <select wire:model.live="payment_status" class="w-full rounded-lg border px-3 py-2 text-sm sm:w-auto sm:min-w-48">
                <option value="">All payments</option>
                <option value="unpaid">Unpaid</option>
                <option value="partial">Partial</option>
                <option value="paid">Paid</option>
            </select>
            <input wire:model.live="date" type="date" class="w-full rounded-lg border px-3 py-2 text-sm sm:w-auto sm:min-w-48">
        </div>
        <a href="{{ route('lab.orders.create') }}" wire:navigate class="app-btn-primary inline-flex items-center justify-center rounded-lg px-5 py-2 text-sm font-semibold transition sm:w-fit">+ New Order</a>
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-[1100px] w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left">Order</th>
                        <th class="px-6 py-3 text-left">Patient</th>
                        <th class="px-6 py-3 text-left">Tests</th>
                        <th class="px-6 py-3 text-right">Amount</th>
                        <th class="px-6 py-3 text-right">Paid</th>
                        <th class="px-6 py-3 text-right">Balance</th>
                        <th class="px-6 py-3 text-left">Payment</th>
                        <th class="px-6 py-3 text-left">Lab status</th>
                        <th class="px-6 py-3 text-left">Date</th>
                        <th class="px-6 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $order)
                        @php $orderStep = $order->workflowStep(); @endphp
                        <tr wire:key="order-{{ $order->id }}">
                            <td class="px-6 py-4 align-top">
                                <a href="{{ route('lab.orders.show', $order) }}" wire:navigate class="app-link-primary font-medium">{{ $order->order_number }}</a>
                                @if($order->is_urgent)
                                    <div class="text-xs font-semibold text-red-600">URGENT</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="font-medium text-gray-800">{{ $order->patient->name }}</div>
                                <div class="text-xs text-gray-500">{{ $order->patient->phone ?: 'No phone' }}</div>
                            </td>
                            <td class="px-6 py-4 align-top whitespace-nowrap text-gray-500">{{ $order->items->count() }}</td>
                            <td class="px-6 py-4 align-top whitespace-nowrap text-right font-medium text-gray-700">Rs. {{ number_format($order->net_amount) }}</td>
                            <td class="px-6 py-4 align-top whitespace-nowrap text-right text-green-700">Rs. {{ number_format($order->invoice?->paid_amount ?? 0) }}</td>
                            <td class="px-6 py-4 align-top whitespace-nowrap text-right {{ ($order->invoice?->balance ?? 0) > 0 ? 'font-medium text-red-600' : 'text-gray-400' }}">Rs. {{ number_format($order->invoice?->balance ?? 0) }}</td>
                            <td class="px-6 py-4 align-top whitespace-nowrap">
                                @if($order->invoice)
                                    <x-status-badge type="payment" :status="$order->invoice->payment_status" />
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top whitespace-nowrap">
                                @if($orderStep === Order::STEP_CANCELLED)
                                    <x-status-badge type="order" :status="$order->status" />
                                @else
                                    <a href="{{ route('lab.samples.show', $order) }}" wire:navigate class="app-link-primary text-xs">
                                        {{ Order::STEP_LABELS[$orderStep] }}
                                    </a>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top whitespace-nowrap text-xs text-gray-400">{{ $order->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4 align-top whitespace-nowrap">
                                <a href="{{ route('lab.orders.show', $order) }}" wire:navigate class="app-link-primary text-xs">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-6 py-8 text-center text-gray-400">No orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-6 py-4">{{ $orders->links() }}</div>
    </div>
</div>
