<div>
    <div class="flex items-center justify-between mb-6">
        <div class="flex gap-3 flex-wrap">
            <input wire:model.live="search" type="text" placeholder="Order # or patient name..." class="border rounded-lg px-4 py-2 text-sm w-64">
            <select wire:model.live="status" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="sample_collected">Sample Collected</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <input wire:model.live="date" type="date" class="border rounded-lg px-3 py-2 text-sm">
        </div>
        <a href="{{ route('lab.orders.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg transition">New Order</a>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-6 py-3 text-left">Order</th>
                    <th class="px-6 py-3 text-left">Patient</th>
                    <th class="px-6 py-3 text-left">Tests</th>
                    <th class="px-6 py-3 text-left">Amount</th>
                    <th class="px-6 py-3 text-left">Payment</th>
                    <th class="px-6 py-3 text-left">Order Status</th>
                    <th class="px-6 py-3 text-left">Release</th>
                    <th class="px-6 py-3 text-left">Date</th>
                    <th class="px-6 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($orders as $order)
                    <tr>
                        <td class="px-6 py-4">
                            <a href="{{ route('lab.orders.show', $order) }}" class="text-blue-600 hover:underline font-medium">{{ $order->order_number }}</a>
                            @if($order->is_urgent)
                                <div class="text-xs font-semibold text-red-600">URGENT</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800">{{ $order->patient->name }}</div>
                            <div class="text-xs text-gray-500">{{ $order->patient->phone ?: 'No phone' }}</div>
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ $order->items->count() }}</td>
                        <td class="px-6 py-4 font-medium text-gray-700">Rs. {{ number_format($order->net_amount) }}</td>
                        <td class="px-6 py-4">
                            @if($order->invoice)
                                @php($paymentColors = ['paid' => 'green', 'partial' => 'yellow', 'unpaid' => 'red'])
                                @php($paymentColor = $paymentColors[$order->invoice->payment_status] ?? 'gray')
                                <span class="px-2 py-1 rounded-full text-xs bg-{{ $paymentColor }}-100 text-{{ $paymentColor }}-700">{{ ucfirst($order->invoice->payment_status) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @php($orderColors = ['pending' => 'yellow', 'sample_collected' => 'blue', 'processing' => 'indigo', 'completed' => 'green', 'cancelled' => 'red'])
                            @php($orderColor = $orderColors[$order->status] ?? 'gray')
                            <span class="px-2 py-1 rounded-full text-xs bg-{{ $orderColor }}-100 text-{{ $orderColor }}-700">{{ \App\Models\Order::STATUSES[$order->status] }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($order->canPrintReport())
                                <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">Released</span>
                            @elseif($order->canReleaseReport())
                                <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700">Ready</span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-600">In progress</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-400 text-xs">{{ $order->created_at->format('d M Y H:i') }}</td>
                        <td class="px-6 py-4">
                            <a href="{{ route('lab.orders.show', $order) }}" class="text-blue-600 hover:underline text-xs">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-6 py-8 text-center text-gray-400">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t">{{ $orders->links() }}</div>
    </div>
</div>