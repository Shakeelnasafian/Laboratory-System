<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex flex-wrap gap-3">
            <input wire:model.live="search" type="text" placeholder="Search order or patient..." class="border rounded-lg px-4 py-2 text-sm w-64">
            <select wire:model.live="queue" class="border rounded-lg px-3 py-2 text-sm">
                <option value="unassigned">Unassigned</option>
                <option value="mine">Mine</option>
                <option value="urgent">Urgent</option>
                <option value="overdue">Overdue</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button wire:click="assignSelectedToMe" class="border px-4 py-2 rounded-lg text-sm">Assign Selected</button>
            <button wire:click="startSelectedProcessing" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">Start Selected</button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Pick</th>
                    <th class="px-6 py-3 text-left">Order</th>
                    <th class="px-6 py-3 text-left">Patient / Test</th>
                    <th class="px-6 py-3 text-left">Accession</th>
                    <th class="px-6 py-3 text-left">Assigned</th>
                    <th class="px-6 py-3 text-left">Due</th>
                    <th class="px-6 py-3 text-left">Status</th>
                    <th class="px-6 py-3 text-left">Notes</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($items as $item)
                    <tr>
                        <td class="px-4 py-4 align-top">
                            <input type="checkbox" value="{{ $item->id }}" wire:model="selectedItems" class="rounded border-gray-300 mt-1">
                        </td>
                        <td class="px-6 py-4 align-top">
                            <a href="{{ route('lab.orders.show', $item->order) }}" class="text-blue-600 hover:underline font-medium">{{ $item->order->order_number }}</a>
                            @if($item->order->is_urgent)
                                <div class="text-xs font-semibold text-red-600 mt-1">URGENT</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 align-top">
                            <div class="font-medium text-gray-800">{{ $item->order->patient->name }}</div>
                            <div class="text-xs text-gray-500">{{ $item->test->name }}</div>
                            <div class="text-xs text-gray-500">{{ $item->test->category?->name ?: 'Uncategorized' }}</div>
                        </td>
                        <td class="px-6 py-4 align-top font-mono text-gray-700">{{ $item->sample?->accession_number ?: 'Pending' }}</td>
                        <td class="px-6 py-4 align-top">
                            <div class="text-sm text-gray-700">{{ $item->assignedTo?->name ?: 'Unassigned' }}</div>
                            @if($item->started_at)
                                <div class="text-xs text-gray-500">Started {{ $item->started_at->diffForHumans() }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 align-top">
                            <div class="text-sm {{ $item->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-700' }}">{{ optional($item->due_at)->format('d M h:i A') ?: 'Not set' }}</div>
                            @if($item->isOverdue())
                                <div class="text-xs text-red-500">Overdue</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 align-top">
                            @php($colors = ['sample_collected' => 'blue', 'processing' => 'indigo', 'completed' => 'green', 'pending' => 'yellow'])
                            @php($color = $colors[$item->status] ?? 'gray')
                            <span class="px-2 py-1 rounded-full text-xs bg-{{ $color }}-100 text-{{ $color }}-700">{{ str_replace('_', ' ', ucfirst($item->status)) }}</span>
                        </td>
                        <td class="px-6 py-4 align-top w-64">
                            <textarea wire:model.defer="notes.{{ $item->id }}" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Bench notes..."></textarea>
                            <button wire:click="saveNotes({{ $item->id }})" class="mt-2 text-xs text-blue-600 hover:underline">Save note</button>
                        </td>
                        <td class="px-6 py-4 align-top text-right">
                            <div class="flex flex-col gap-2 items-end">
                                <button wire:click="assignToMe({{ $item->id }})" class="border px-3 py-2 rounded-lg text-sm">Assign to me</button>
                                @if($item->status !== 'processing')
                                    <button wire:click="startProcessing({{ $item->id }})" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm">Start</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-10 text-center text-gray-400">No worklist items match the current queue.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t">{{ $items->links() }}</div>
    </div>
</div>