<div>
    <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow p-4"><p class="text-xs text-gray-500">Today's Orders</p><p class="text-2xl font-bold text-gray-800">{{ $todayOrders }}</p></div>
        <div class="bg-white rounded-xl shadow p-4"><p class="text-xs text-gray-500">Today's Patients</p><p class="text-2xl font-bold text-blue-600">{{ $todayPatients }}</p></div>
        <div class="bg-white rounded-xl shadow p-4"><p class="text-xs text-gray-500">Pending Collection</p><p class="text-2xl font-bold text-yellow-500">{{ $pendingCollection }}</p></div>
        <div class="bg-white rounded-xl shadow p-4"><p class="text-xs text-gray-500">In Processing</p><p class="text-2xl font-bold text-indigo-600">{{ $processingItems }}</p></div>
        <div class="bg-white rounded-xl shadow p-4"><p class="text-xs text-gray-500">Overdue Items</p><p class="text-2xl font-bold text-red-600">{{ $overdueItems }}</p></div>
        <div class="bg-white rounded-xl shadow p-4"><p class="text-xs text-gray-500">Completed Today</p><p class="text-2xl font-bold text-green-600">{{ $completedItemsToday }}</p></div>
        <div class="bg-white rounded-xl shadow p-4"><p class="text-xs text-gray-500">Today's Revenue</p><p class="text-2xl font-bold text-emerald-600">Rs. {{ number_format($todayRevenue) }}</p></div>
        <div class="bg-white rounded-xl shadow p-4"><p class="text-xs text-gray-500">Total Patients</p><p class="text-2xl font-bold text-gray-800">{{ $totalPatients }}</p></div>
    </div>

    <div class="flex flex-wrap gap-3 mb-6">
        <a href="{{ route('lab.orders.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition">New Order</a>
        <a href="{{ route('lab.samples.collection') }}" class="bg-white border hover:bg-gray-50 text-gray-700 px-5 py-2 rounded-lg text-sm font-medium transition">Samples</a>
        <a href="{{ route('lab.worklists.index') }}" class="bg-white border hover:bg-gray-50 text-gray-700 px-5 py-2 rounded-lg text-sm font-medium transition">Worklists</a>
        <a href="{{ route('lab.results.release') }}" class="bg-white border hover:bg-gray-50 text-gray-700 px-5 py-2 rounded-lg text-sm font-medium transition">Release Queue</a>
    </div>

    <div class="bg-white rounded-xl shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="font-semibold text-gray-800">Recent Orders</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-6 py-3 text-left">Order</th>
                    <th class="px-6 py-3 text-left">Patient</th>
                    <th class="px-6 py-3 text-left">Tests</th>
                    <th class="px-6 py-3 text-left">Status</th>
                    <th class="px-6 py-3 text-left">Release</th>
                    <th class="px-6 py-3 text-left">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($recentOrders as $order)
                    <tr>
                        <td class="px-6 py-4"><a href="{{ route('lab.orders.show', $order) }}" class="text-blue-600 hover:underline font-medium">{{ $order->order_number }}</a></td>
                        <td class="px-6 py-4 text-gray-700">{{ $order->patient->name }}</td>
                        <td class="px-6 py-4 text-gray-500">{{ $order->items->count() }} test(s)</td>
                        <td class="px-6 py-4">
                            @php($colors = ['pending' => 'yellow', 'sample_collected' => 'blue', 'processing' => 'indigo', 'completed' => 'green', 'cancelled' => 'red'])
                            @php($color = $colors[$order->status] ?? 'gray')
                            <span class="px-2 py-1 rounded-full text-xs bg-{{ $color }}-100 text-{{ $color }}-700">{{ \App\Models\Order::STATUSES[$order->status] }}</span>
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
                        <td class="px-6 py-4 text-gray-400">{{ $order->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No recent orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>