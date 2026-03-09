<div>
    @include('livewire.lab.partials.sample-tabs')

    <div class="flex items-center justify-between gap-3 mb-6">
        <input wire:model.live="search" type="text" placeholder="Search accession, order, or patient..." class="border rounded-lg px-4 py-2 text-sm w-72">
        <div class="text-sm text-gray-500">Rejected samples stay here until recollection is completed.</div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-6 py-3 text-left">Accession</th>
                    <th class="px-6 py-3 text-left">Order</th>
                    <th class="px-6 py-3 text-left">Patient</th>
                    <th class="px-6 py-3 text-left">Test</th>
                    <th class="px-6 py-3 text-left">Reason</th>
                    <th class="px-6 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($samples as $sample)
                    <tr>
                        <td class="px-6 py-4 font-mono text-red-600">{{ $sample->accession_number }}</td>
                        <td class="px-6 py-4"><a href="{{ route('lab.orders.show', $sample->orderItem->order) }}" class="text-blue-600 hover:underline">{{ $sample->orderItem->order->order_number }}</a></td>
                        <td class="px-6 py-4">{{ $sample->orderItem->order->patient->name }}</td>
                        <td class="px-6 py-4">{{ $sample->orderItem->test->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $sample->rejection_reason }}</td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="openRecollect({{ $sample->id }})" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">Recollect</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400">No rejected samples right now.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t">{{ $samples->links() }}</div>
    </div>

    @if($showRecollectModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Recollect Sample</h3>
                <form wire:submit="recollect" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sample Type</label>
                        <input wire:model="sample_type" type="text" class="w-full border rounded-lg px-3 py-2 text-sm">
                        @error('sample_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Container</label>
                        <input wire:model="container" type="text" class="w-full border rounded-lg px-3 py-2 text-sm">
                        @error('container') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="$set('showRecollectModal', false)" class="border px-4 py-2 rounded-lg text-sm">Cancel</button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm">Save Recollection</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>