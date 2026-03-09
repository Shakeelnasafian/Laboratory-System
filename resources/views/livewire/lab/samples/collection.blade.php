<div>
    @include('livewire.lab.partials.sample-tabs')

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex flex-wrap gap-3">
            <input wire:model.live="search" type="text" placeholder="Search order or patient..." class="border rounded-lg px-4 py-2 text-sm w-64">
            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                <input wire:model.live="onlyUrgent" type="checkbox" class="rounded border-gray-300">
                Urgent only
            </label>
        </div>
        <a href="{{ route('lab.worklists.index') }}" class="text-sm text-blue-600 hover:underline">Go to worklists</a>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-6 py-3 text-left">Order</th>
                    <th class="px-6 py-3 text-left">Patient</th>
                    <th class="px-6 py-3 text-left">Test</th>
                    <th class="px-6 py-3 text-left">Sample Type</th>
                    <th class="px-6 py-3 text-left">Queue State</th>
                    <th class="px-6 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($items as $item)
                    <tr>
                        <td class="px-6 py-4">
                            <a href="{{ route('lab.orders.show', $item->order) }}" class="font-medium text-blue-600 hover:underline">{{ $item->order->order_number }}</a>
                            @if($item->order->is_urgent)
                                <span class="ml-2 text-xs font-semibold text-red-600">URGENT</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800">{{ $item->order->patient->name }}</div>
                            <div class="text-xs text-gray-500">{{ $item->order->patient->phone ?: 'No phone' }}</div>
                        </td>
                        <td class="px-6 py-4 text-gray-700">
                            <div>{{ $item->test->name }}</div>
                            <div class="text-xs text-gray-500">{{ $item->test->code ?: 'No code' }}</div>
                        </td>
                        <td class="px-6 py-4 text-gray-700">{{ $item->sample?->sample_type ?: ($item->test->sample_type ?: 'General') }}</td>
                        <td class="px-6 py-4">
                            @if($item->sample?->status === 'rejected')
                                <div class="text-xs font-medium text-red-600">Rejected for recollect</div>
                                <div class="text-xs text-gray-500">{{ $item->sample->rejection_reason }}</div>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-700">Awaiting collection</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="openCollect({{ $item->id }})" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                                {{ $item->sample?->status === 'rejected' ? 'Recollect' : 'Collect' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400">No order items need sample collection.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t">{{ $items->links() }}</div>
    </div>

    @if($showCollectModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Collect Sample</h3>
                <form wire:submit="saveCollection" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sample Type</label>
                        <input wire:model="sample_type" type="text" class="w-full border rounded-lg px-3 py-2 text-sm">
                        @error('sample_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Container</label>
                        <input wire:model="container" type="text" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="EDTA tube, serum cup, etc.">
                        @error('container') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-lg bg-blue-50 text-blue-700 text-sm px-4 py-3">
                        Saving collection generates the accession label and moves the item into the receive queue.
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="$set('showCollectModal', false)" class="border px-4 py-2 rounded-lg text-sm">Cancel</button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm">Save Collection</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>