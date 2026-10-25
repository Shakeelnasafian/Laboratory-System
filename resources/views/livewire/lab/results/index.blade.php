<div>
    <div class="mb-6 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="grid gap-3 sm:grid-cols-2 xl:flex xl:flex-wrap">
            <input wire:model.live.debounce.500ms="search" type="text" placeholder="Search order or patient..." class="w-full rounded-lg border px-4 py-2 text-sm sm:min-w-80 xl:w-80">
            <select wire:model.live="status" class="w-full rounded-lg border px-3 py-2 text-sm sm:w-auto sm:min-w-36">
                <option value="">All</option>
                <option value="pending">Pending Entry</option>
                <option value="draft">Draft</option>
                <option value="verified">Verified</option>
                <option value="released">Released</option>
                <option value="critical">Critical</option>
            </select>
        </div>
        <a href="{{ route('lab.results.release') }}" wire:navigate class="app-link-primary text-sm">Go to release queue</a>
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-[980px] w-full text-sm">
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
                            <td class="px-6 py-4 align-top"><a href="{{ route('lab.samples.show', $item->order) }}" wire:navigate class="app-link-primary font-medium">{{ $item->order->order_number }}</a></td>
                            <td class="px-6 py-4 align-top">{{ $item->order->patient->name }}</td>
                            <td class="px-6 py-4 align-top">
                                <div>{{ $item->test->name }}</div>
                                <div class="text-xs text-gray-500">{{ $item->sample?->accession_number ?: 'Sample pending' }}</div>
                            </td>
                            <td class="px-6 py-4 align-top">
                                @if($item->result)
                                    <div class="{{ $item->result->is_abnormal ? 'font-semibold text-red-600' : 'text-gray-800' }}">{{ $item->result->value }} {{ $item->result->unit }}</div>
                                    <div class="text-xs text-gray-500">{{ $item->result->normal_range ?: 'No range' }}</div>
                                @else
                                    <span class="text-gray-400">Not entered</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top whitespace-nowrap">
                                @if($item->result)
                                    <x-status-badge type="result" :status="$item->result->status" />
                                    @if($item->result->flag === 'critical')
                                        <div class="mt-1"><x-status-badge type="signal" status="critical" /></div>
                                    @endif
                                @else
                                    <x-status-badge type="queue" status="waiting_for_draft" />
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top whitespace-nowrap text-right">
                                <div class="flex justify-end gap-3">
                                    <button wire:click="openResultEntry({{ $item->id }})" class="app-link-primary text-sm">{{ $item->result ? 'Edit' : 'Enter' }}</button>
                                    @if($item->result && $item->result->status === 'draft' && $canVerify)
                                        <button wire:click="verify({{ $item->id }})" class="text-sm text-emerald-600 hover:underline">Verify</button>
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
        </div>
        <div class="border-t px-6 py-4">{{ $items->links() }}</div>
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="mb-4 text-lg font-semibold text-gray-800">Enter Result</h3>
                <form wire:submit="saveResult" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Result Value</label>
                        <input wire:model="value" type="text" class="w-full rounded-lg border px-3 py-2 text-sm" autofocus>
                        @error('value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Unit</label>
                            <input wire:model="unit" type="text" class="w-full rounded-lg border px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Flag</label>
                            <select wire:model="flag" class="w-full rounded-lg border px-3 py-2 text-sm">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="low">Low</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Normal Range</label>
                        <input wire:model="normal_range" type="text" class="w-full rounded-lg border px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Remarks</label>
                        <textarea wire:model="remarks" rows="3" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="Required for critical results"></textarea>
                        @error('remarks') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-lg bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                        Saving stores the result as draft. Verification and report release happen separately.
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="$set('showModal', false)" class="rounded-lg border px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="app-btn-primary rounded-lg px-5 py-2 text-sm">Save Draft</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
