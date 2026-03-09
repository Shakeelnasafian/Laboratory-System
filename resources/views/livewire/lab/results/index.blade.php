<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex flex-wrap gap-3">
            <input wire:model.live="search" type="text" placeholder="Search order or patient..." class="border rounded-lg px-4 py-2 text-sm w-64">
            <select wire:model.live="status" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">All</option>
                <option value="pending">Pending Entry</option>
                <option value="draft">Draft</option>
                <option value="verified">Verified</option>
                <option value="released">Released</option>
                <option value="critical">Critical</option>
            </select>
        </div>
        <a href="{{ route('lab.results.release') }}" class="text-sm text-blue-600 hover:underline">Go to release queue</a>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-6 py-3 text-left">Order</th>
                    <th class="px-6 py-3 text-left">Patient</th>
                    <th class="px-6 py-3 text-left">Test / Sample</th>
                    <th class="px-6 py-3 text-left">Result</th>
                    <th class="px-6 py-3 text-left">Workflow</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($items as $item)
                    <tr>
                        <td class="px-6 py-4"><a href="{{ route('lab.orders.show', $item->order) }}" class="text-blue-600 hover:underline font-medium">{{ $item->order->order_number }}</a></td>
                        <td class="px-6 py-4">{{ $item->order->patient->name }}</td>
                        <td class="px-6 py-4">
                            <div>{{ $item->test->name }}</div>
                            <div class="text-xs text-gray-500">{{ $item->sample?->accession_number ?: 'Sample pending' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($item->result)
                                <div class="{{ $item->result->is_abnormal ? 'text-red-600 font-semibold' : 'text-gray-800' }}">{{ $item->result->value }} {{ $item->result->unit }}</div>
                                <div class="text-xs text-gray-500">{{ $item->result->normal_range ?: 'No range' }}</div>
                            @else
                                <span class="text-gray-400">Not entered</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($item->result)
                                @php($statusColors = ['draft' => 'yellow', 'verified' => 'blue', 'released' => 'green'])
                                @php($statusColor = $statusColors[$item->result->status] ?? 'gray')
                                <span class="px-2 py-1 rounded-full text-xs bg-{{ $statusColor }}-100 text-{{ $statusColor }}-700">{{ ucfirst($item->result->status) }}</span>
                                @if($item->result->flag === 'critical')
                                    <div class="text-xs text-red-600 font-medium mt-1">Critical</div>
                                @endif
                            @else
                                <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-600">Waiting for draft</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button wire:click="openResultEntry({{ $item->id }})" class="text-blue-600 hover:underline text-sm">{{ $item->result ? 'Edit' : 'Enter' }}</button>
                                @if($item->result && $item->result->status === 'draft' && auth()->user()->canVerifyResults())
                                    <button wire:click="verify({{ $item->id }})" class="text-emerald-600 hover:underline text-sm">Verify</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400">No result items found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t">{{ $items->links() }}</div>
    </div>

    @if($showModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Enter Result</h3>
                <form wire:submit="saveResult" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Result Value</label>
                        <input wire:model="value" type="text" class="w-full border rounded-lg px-3 py-2 text-sm" autofocus>
                        @error('value') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                            <input wire:model="unit" type="text" class="w-full border rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Flag</label>
                            <select wire:model="flag" class="w-full border rounded-lg px-3 py-2 text-sm">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="low">Low</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Normal Range</label>
                        <input wire:model="normal_range" type="text" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                        <textarea wire:model="remarks" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Required for critical results"></textarea>
                        @error('remarks') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-lg bg-yellow-50 text-yellow-800 text-sm px-4 py-3">
                        Saving stores the result as draft. Verification and report release happen separately.
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="$set('showModal', false)" class="border px-4 py-2 rounded-lg text-sm">Cancel</button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm">Save Draft</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>