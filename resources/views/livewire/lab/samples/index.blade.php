@use('App\Models\Order')
@use('App\Livewire\Lab\Samples\SampleIndex')
<div>
    <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="grid gap-3 sm:grid-cols-2 xl:flex xl:flex-wrap">
            <input wire:model.live.debounce.500ms="search" type="text" placeholder="Order # or patient name..." class="w-full rounded-lg border px-4 py-2 text-sm sm:min-w-80 xl:w-80">
            <input wire:model.live="date" type="date" class="w-full rounded-lg border px-3 py-2 text-sm sm:w-auto sm:min-w-48">
        </div>
        <a href="{{ route('lab.orders.create') }}" wire:navigate class="app-btn-primary inline-flex items-center justify-center rounded-lg px-5 py-2 text-sm font-semibold transition sm:w-fit">+ New Order</a>
    </div>

    {{-- Work queue: one tab per next step --}}
    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        @foreach(SampleIndex::QUEUES as $key => $label)
            <button wire:click="setQueue('{{ $key }}')" type="button" @class([
                'inline-flex shrink-0 items-center gap-2 rounded-full border px-4 py-1.5 text-sm transition',
                'border-blue-600 bg-blue-600 text-white' => $queue === $key,
                'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' => $queue !== $key,
            ])>
                {{ $label }}
                <span @class([
                    'rounded-full px-2 text-xs font-semibold',
                    'bg-white/20 text-white' => $queue === $key,
                    'bg-gray-100 text-gray-600' => $queue !== $key,
                ])>{{ $counts[$key] ?? 0 }}</span>
            </button>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl bg-white shadow">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left">Sample</th>
                        <th class="px-6 py-3 text-left">Patient</th>
                        <th class="px-6 py-3 text-left">Tests</th>
                        <th class="px-6 py-3 text-left">Sample type</th>
                        <th class="px-6 py-3 text-left">Registered</th>
                        <th class="px-6 py-3 text-left">Next step</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $order)
                        @php
                            $orderStep = $order->workflowStep();
                            $accession = $order->items->first(fn ($item) => $item->sample?->isCollected())?->sample->accession_number;
                            $types = $order->items->map(fn ($item) => $item->sample?->sample_type ?: $item->test->sample_type)->filter()->unique();
                        @endphp
                        <tr wire:key="sample-{{ $order->id }}" class="cursor-pointer hover:bg-gray-50" onclick="if (!event.target.closest('a')) this.querySelector('[data-open]').click()">
                            <td class="px-6 py-4 align-top">
                                <div class="font-mono font-medium text-gray-800">{{ $accession ?? '—' }}</div>
                                <div class="text-xs text-gray-500">{{ $order->order_number }}</div>
                                @if($order->is_urgent)
                                    <div class="text-xs font-semibold text-red-600">URGENT</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="font-medium text-gray-800">{{ $order->patient->name }}</div>
                                <div class="text-xs text-gray-500">{{ $order->patient->age ?? '' }} {{ $order->patient->age_unit }} · {{ ucfirst($order->patient->gender) }}</div>
                            </td>
                            <td class="px-6 py-4 align-top text-gray-600">
                                {{ $order->items->pluck('test.name')->take(3)->join(', ') }}
                                @if($order->items->count() > 3)
                                    <span class="text-xs text-gray-400">+{{ $order->items->count() - 3 }} more</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top text-gray-600">{{ $types->join(', ') ?: '—' }}</td>
                            <td class="px-6 py-4 align-top whitespace-nowrap text-xs text-gray-400">{{ $order->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4 align-top whitespace-nowrap">
                                <a href="{{ route('lab.samples.show', $order) }}" wire:navigate data-open @class([
                                    'inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold',
                                    'bg-green-600 text-white hover:bg-green-700' => $orderStep === Order::STEP_DONE,
                                    'bg-gray-100 text-gray-500' => $orderStep === Order::STEP_CANCELLED,
                                    'bg-blue-600 text-white hover:bg-blue-700' => ! in_array($orderStep, [Order::STEP_DONE, Order::STEP_CANCELLED], true),
                                ])>
                                    {{ $orderStep === Order::STEP_DONE ? 'Print report' : Order::STEP_LABELS[$orderStep] }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No samples here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-6 py-4">{{ $orders->links() }}</div>
    </div>
</div>
