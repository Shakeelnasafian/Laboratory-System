<div>
    @include('livewire.lab.partials.sample-tabs')

    <div class="mb-6 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <input wire:model.live="search" type="text" placeholder="Search order or patient..." class="w-full rounded-lg border px-4 py-2 text-sm sm:w-80">
            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                <input wire:model.live="onlyUrgent" type="checkbox" class="rounded border-gray-300">
                Urgent only
            </label>
        </div>
        <a href="{{ route('lab.worklists.index') }}" wire:navigate class="app-link-primary text-sm">Go to worklists</a>
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-[900px] w-full text-sm">
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
                            <td class="px-6 py-4 align-top">
                                <a href="{{ route('lab.samples.show', $item->order) }}" wire:navigate class="app-link-primary font-medium">{{ $item->order->order_number }}</a>
                                @if($item->order->is_urgent)
                                    <span class="ml-2 text-xs font-semibold text-red-600">URGENT</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="font-medium text-gray-800">{{ $item->order->patient->name }}</div>
                                <div class="text-xs text-gray-500">{{ $item->order->patient->phone ?: 'No phone' }}</div>
                            </td>
                            <td class="px-6 py-4 align-top text-gray-700">
                                <div>{{ $item->test->name }}</div>
                                <div class="text-xs text-gray-500">{{ $item->test->code ?: 'No code' }}</div>
                            </td>
                            <td class="px-6 py-4 align-top text-gray-700">{{ $item->sample?->sample_type ?: ($item->test->sample_type ?: 'General') }}</td>
                            <td class="px-6 py-4 align-top">
                                @if($item->sample?->status === 'rejected')
                                    <x-status-badge type="queue" status="rejected_for_recollect" />
                                    <div class="text-xs text-gray-500">{{ $item->sample->rejection_reason }}</div>
                                @else
                                    <x-status-badge type="queue" status="awaiting_collection" />
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top whitespace-nowrap text-right">
                                <button wire:click="openCollect({{ $item->id }})" class="app-btn-primary rounded-lg px-4 py-2 text-sm">
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
        </div>
        <div class="border-t px-6 py-4">{{ $items->links() }}</div>
    </div>

    @if($showCollectModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold text-gray-800">Collect Sample</h3>
                <form wire:submit="saveCollection" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Sample Type</label>
                        <input wire:model="sample_type" type="text" class="w-full rounded-lg border px-3 py-2 text-sm">
                        @error('sample_type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Container</label>
                        <input wire:model="container" type="text" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="EDTA tube, serum cup, etc.">
                        @error('container') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-700">
                        Saving collection generates the accession label and moves the item into the receive queue.
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="$set('showCollectModal', false)" class="rounded-lg border px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="app-btn-primary rounded-lg px-5 py-2 text-sm">Save Collection</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
