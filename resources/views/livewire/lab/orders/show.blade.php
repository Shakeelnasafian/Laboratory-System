<div class="max-w-6xl mx-auto space-y-6">
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Order #{{ $order->order_number }}</h2>
                <p class="text-sm text-gray-500 mt-1">Placed {{ $order->created_at->format('d M Y, h:i A') }} by {{ $order->createdBy->name }}</p>
                @if($order->is_urgent)
                    <span class="inline-flex mt-3 px-3 py-1 rounded-full text-xs bg-red-100 text-red-700">Urgent</span>
                @endif
            </div>
            <div class="flex flex-wrap gap-2 justify-end">
                @if($order->canPrintReport())
                    <a href="{{ route('lab.orders.report', $order) }}" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm">Print Report</a>
                @else
                    <span class="px-4 py-2 rounded-lg text-sm bg-yellow-50 text-yellow-800">Release report after all results are verified.</span>
                @endif
                <a href="{{ route('lab.samples.collection') }}" class="border px-4 py-2 rounded-lg text-sm">Samples</a>
                <a href="{{ route('lab.worklists.index') }}" class="border px-4 py-2 rounded-lg text-sm">Worklists</a>
                <a href="{{ route('lab.results.release') }}" class="border px-4 py-2 rounded-lg text-sm">Release Queue</a>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-3 text-sm">
            @php($colors = ['pending' => 'yellow', 'sample_collected' => 'blue', 'processing' => 'indigo', 'completed' => 'green', 'cancelled' => 'red'])
            @php($color = $colors[$order->status] ?? 'gray')
            <span class="px-3 py-1 rounded-full bg-{{ $color }}-100 text-{{ $color }}-700 font-medium">{{ \App\Models\Order::STATUSES[$order->status] }}</span>
            @if($order->canPrintReport())
                <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 font-medium">Report Released</span>
            @elseif($order->canReleaseReport())
                <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 font-medium">Ready for Release</span>
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
                                @php($statusColors = ['draft' => 'yellow', 'verified' => 'blue', 'released' => 'green'])
                                @php($statusColor = $statusColors[$item->result->status] ?? 'gray')
                                <span class="px-2 py-1 rounded-full text-xs bg-{{ $statusColor }}-100 text-{{ $statusColor }}-700">{{ ucfirst($item->result->status) }}</span>
                                @if($item->result->flag === 'critical')
                                    <div class="text-xs text-red-600 mt-1">Critical</div>
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
                <div><p class="text-gray-500">Status</p><span class="px-2 py-1 rounded-full text-xs {{ $order->invoice->payment_status === 'paid' ? 'bg-green-100 text-green-700' : ($order->invoice->payment_status === 'partial' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">{{ ucfirst($order->invoice->payment_status) }}</span></div>
            </div>
        </div>
    @endif

    <div class="flex justify-start">
        <a href="{{ route('lab.orders.index') }}" class="text-blue-600 hover:underline text-sm">Back to Orders</a>
    </div>
</div>