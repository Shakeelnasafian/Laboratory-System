<div>
    @include('livewire.lab.partials.sample-tabs')

    <div class="mb-6 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <input wire:model.live="search" type="text" placeholder="Search accession, order, or patient..." class="w-full rounded-lg border px-4 py-2 text-sm sm:w-80">
        <div class="text-sm text-gray-500">Rejected samples stay here until recollection is completed.</div>
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-[900px] w-full text-sm">
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
                            <td class="px-6 py-4 align-top whitespace-nowrap font-mono text-red-600">{{ $sample->accession_number }}</td>
                            <td class="px-6 py-4 align-top"><a href="{{ route('lab.samples.show', $sample->orderItem->order) }}" wire:navigate class="app-link-primary">{{ $sample->orderItem->order->order_number }}</a></td>
                            <td class="px-6 py-4 align-top">{{ $sample->orderItem->order->patient->name }}</td>
                            <td class="px-6 py-4 align-top">{{ $sample->orderItem->test->name }}</td>
                            <td class="px-6 py-4 align-top text-sm text-gray-600">{{ $sample->rejection_reason }}</td>
                            <td class="px-6 py-4 align-top whitespace-nowrap text-right">
                                <button wire:click="openRecollect({{ $sample->id }})" class="app-btn-primary rounded-lg px-4 py-2 text-sm">Recollect</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-400">No rejected samples right now.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-6 py-4">{{ $samples->links() }}</div>
    </div>

    @if($showRecollectModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold text-gray-800">Recollect Sample</h3>
                <form wire:submit="recollect" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Sample Type</label>
                        <input wire:model="sample_type" type="text" class="w-full rounded-lg border px-3 py-2 text-sm">
                        @error('sample_type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Container</label>
                        <input wire:model="container" type="text" class="w-full rounded-lg border px-3 py-2 text-sm">
                        @error('container') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="$set('showRecollectModal', false)" class="rounded-lg border px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="app-btn-primary rounded-lg px-5 py-2 text-sm">Save Recollection</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
