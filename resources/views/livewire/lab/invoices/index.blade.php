<div>
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-4 shadow dark:bg-gray-800">
            <p class="text-xs text-gray-500">Today's Billing</p>
            <p class="text-xl font-bold text-gray-800 dark:text-white">Rs. {{ number_format($todayTotal) }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow dark:bg-gray-800">
            <p class="text-xs text-gray-500">Collected Today</p>
            <p class="text-xl font-bold text-green-600">Rs. {{ number_format($todayCollected) }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow dark:bg-gray-800">
            <p class="text-xs text-gray-500">Outstanding</p>
            <p class="text-xl font-bold text-red-500">Rs. {{ number_format($outstanding) }}</p>
        </div>
    </div>

    <div class="mb-6 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="grid gap-3 sm:grid-cols-2 xl:flex xl:flex-wrap">
            <input wire:model.live="search" type="text" placeholder="Search invoice # or patient..." class="w-full rounded-lg border px-4 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:min-w-80 xl:w-80">
            <select wire:model.live="payment_status" class="w-full rounded-lg border px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:w-auto sm:min-w-24">
                <option value="">All</option>
                <option value="paid">Paid</option>
                <option value="partial">Partial</option>
                <option value="unpaid">Unpaid</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-[1020px] w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-6 py-3 text-left">Invoice #</th>
                        <th class="px-6 py-3 text-left">Patient</th>
                        <th class="px-6 py-3 text-left">Order</th>
                        <th class="px-6 py-3 text-right">Total</th>
                        <th class="px-6 py-3 text-right">Paid</th>
                        <th class="px-6 py-3 text-right">Balance</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Date</th>
                        <th class="px-6 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    @forelse($invoices as $inv)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-3 align-top font-mono text-xs text-gray-500">{{ $inv->invoice_number }}</td>
                        <td class="px-6 py-3 align-top font-medium text-gray-800 dark:text-white">{{ $inv->order->patient->name ?? '-' }}</td>
                        <td class="px-6 py-3 align-top">
                            <a href="{{ route('lab.orders.show', $inv->order) }}" wire:navigate class="app-link-primary text-xs">{{ $inv->order->order_number }}</a>
                        </td>
                        <td class="px-6 py-3 align-top whitespace-nowrap text-right text-gray-700 dark:text-gray-200">Rs. {{ number_format($inv->total) }}</td>
                        <td class="px-6 py-3 align-top whitespace-nowrap text-right text-green-600">Rs. {{ number_format($inv->paid_amount) }}</td>
                        <td class="px-6 py-3 align-top whitespace-nowrap text-right {{ $inv->balance > 0 ? 'font-medium text-red-500' : 'text-gray-400' }}">
                            Rs. {{ number_format($inv->balance) }}
                        </td>
                        <td class="px-6 py-3 align-top whitespace-nowrap">
                            <x-status-badge type="payment" :status="$inv->payment_status" />
                        </td>
                        <td class="px-6 py-3 align-top whitespace-nowrap text-xs text-gray-400">{{ $inv->created_at->format('d M Y') }}</td>
                        <td class="px-6 py-3 align-top whitespace-nowrap">
                            @if($inv->payment_status !== 'paid')
                                <button wire:click="markPaid({{ $inv->id }})" wire:confirm="Mark this invoice as fully paid?" class="text-xs text-green-600 hover:underline">Mark Paid</button>
                            @else
                                <span class="text-xs text-gray-400">Done</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-6 py-8 text-center text-gray-400">No invoices found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-6 py-4 dark:border-gray-700">{{ $invoices->links() }}</div>
    </div>
</div>
