<div>
    @include('livewire.lab.partials.sample-tabs')

    <div class="mb-6 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <input wire:model.live="search" type="text" placeholder="Search accession, order, or patient..." class="w-full rounded-lg border px-4 py-2 text-sm sm:w-80">
        <div class="text-sm text-gray-500">Received samples become eligible for worklists immediately.</div>
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-[980px] w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left">Accession</th>
                        <th class="px-6 py-3 text-left">Order</th>
                        <th class="px-6 py-3 text-left">Patient</th>
                        <th class="px-6 py-3 text-left">Collected</th>
                        <th class="px-6 py-3 text-left">Test</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($samples as $sample)
                        <tr>
                            <td class="px-6 py-4 align-top whitespace-nowrap font-mono text-blue-700">{{ $sample->accession_number }}</td>
                            <td class="px-6 py-4 align-top">
                                <a href="{{ route('lab.samples.show', $sample->orderItem->order) }}" wire:navigate class="app-link-primary">{{ $sample->orderItem->order->order_number }}</a>
                            </td>
                            <td class="px-6 py-4 align-top">{{ $sample->orderItem->order->patient->name }}</td>
                            <td class="px-6 py-4 align-top text-gray-600">
                                {{ $sample->collectedBy?->name ?: 'System' }}
                                <div class="whitespace-nowrap text-xs text-gray-500">{{ optional($sample->collected_at)->format('d M Y h:i A') }}</div>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div>{{ $sample->orderItem->test->name }}</div>
                                <div class="text-xs text-gray-500">{{ $sample->sample_type }}</div>
                            </td>
                            <td class="px-6 py-4 align-top whitespace-nowrap text-right">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="receive({{ $sample->id }})" class="app-btn-success rounded-lg px-3 py-2 text-sm">Receive</button>
                                    <button wire:click="openReject({{ $sample->id }})" class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-600">Reject</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-400">No samples are awaiting receipt.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-6 py-4">{{ $samples->links() }}</div>
    </div>

    @if($showRejectModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold text-gray-800">Reject Sample</h3>
                <form wire:submit="reject" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Reason for rejection</label>
                        <textarea wire:model="rejection_reason" rows="4" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="Clotted sample, insufficient volume, wrong container, etc."></textarea>
                        @error('rejection_reason') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="$set('showRejectModal', false)" class="rounded-lg border px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="rounded-lg bg-red-600 px-5 py-2 text-sm text-white hover:bg-red-700">Reject Sample</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
