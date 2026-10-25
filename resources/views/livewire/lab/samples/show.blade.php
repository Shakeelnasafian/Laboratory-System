@use('App\Models\Order')
@use('App\Models\Result')
@php
    $steps = [
        ['key' => 'registered', 'label' => 'Registered'],
        ['key' => Order::STEP_COLLECT, 'label' => 'Sample collected'],
        ['key' => Order::STEP_RESULTS, 'label' => 'Results entered'],
        ['key' => Order::STEP_VERIFY, 'label' => 'Verified'],
        ['key' => Order::STEP_RELEASE, 'label' => 'Report released'],
    ];
    // Number of steps already completed for the current workflow step.
    $completed = [
        Order::STEP_COLLECT => 1,
        Order::STEP_RESULTS => 2,
        Order::STEP_VERIFY => 3,
        Order::STEP_RELEASE => 4,
        Order::STEP_DONE => 5,
        Order::STEP_CANCELLED => 0,
    ][$step];
    $invoice = $order->invoice;
    $hasBalance = $invoice && $invoice->balance > 0;
    $accessions = $order->items->map(fn ($item) => $item->sample?->isCollected() ? $item->sample->accession_number : null)->filter()->unique()->values();
    $sampleTypes = $order->items->map(fn ($item) => $item->sample?->sample_type ?: $item->test->sample_type)->filter()->unique()->values();
    $flagClasses = [
        Result::FLAG_NORMAL => 'border-gray-300 text-gray-700',
        Result::FLAG_HIGH => 'border-orange-400 bg-orange-50 text-orange-700',
        Result::FLAG_LOW => 'border-sky-400 bg-sky-50 text-sky-700',
        Result::FLAG_CRITICAL => 'border-red-500 bg-red-50 text-red-700 font-semibold',
    ];
@endphp

<div class="mx-auto max-w-6xl space-y-5">
    {{-- Patient + sample header --}}
    <div class="rounded-xl bg-white p-5 shadow">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-bold text-gray-800">{{ $order->patient->name }}</h2>
                    @if($order->is_urgent)
                        <x-status-badge type="signal" status="urgent" />
                    @endif
                </div>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $order->patient->age ?? 'N/A' }} {{ $order->patient->age_unit }} · {{ ucfirst($order->patient->gender) }}
                    · <span class="font-mono">{{ $order->patient->patient_id }}</span>
                    @if($order->patient->phone) · {{ $order->patient->phone }} @endif
                </p>
                <p class="mt-1 text-xs text-gray-500">
                    Order <a href="{{ route('lab.orders.show', $order) }}" wire:navigate class="app-link-primary font-mono font-medium">{{ $order->order_number }}</a>
                    · {{ $order->created_at->format('d M Y, h:i A') }}
                    @if($order->referred_by) · Ref: {{ $order->referred_by }} @endif
                </p>
            </div>

            <div class="text-right text-sm">
                <p class="text-xs text-gray-500">Sample ID</p>
                <p class="font-mono font-semibold text-gray-800">{{ $accessions->isNotEmpty() ? $accessions->join(', ') : 'Not collected yet' }}</p>
                @if($sampleTypes->isNotEmpty())
                    <p class="text-xs text-gray-500">{{ $sampleTypes->join(', ') }}</p>
                @endif
            </div>
        </div>

        {{-- Progress --}}
        @if($step !== Order::STEP_CANCELLED)
            <ol class="mt-5 grid grid-cols-5 gap-2">
                @foreach($steps as $index => $item)
                    @php $done = $index < $completed; $current = $index === $completed; @endphp
                    <li class="flex flex-col items-center text-center">
                        <span @class([
                            'flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold',
                            'bg-green-600 text-white' => $done,
                            'bg-blue-600 text-white ring-4 ring-blue-100' => $current,
                            'bg-gray-200 text-gray-500' => ! $done && ! $current,
                        ])>
                            @if($done) &#10003; @else {{ $index + 1 }} @endif
                        </span>
                        <span @class([
                            'mt-1 text-[11px] leading-tight sm:text-xs',
                            'font-semibold text-blue-700' => $current,
                            'text-gray-600' => ! $current,
                        ])>{{ $item['label'] }}</span>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

    {{-- Feedback --}}
    @if($notice)
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ $notice }}</div>
    @endif
    @error('grid')
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    {{-- Next step --}}
    @if($step === Order::STEP_CANCELLED)
        <div class="rounded-xl border border-red-200 bg-red-50 p-5 text-red-800">
            <p class="font-semibold">This order was cancelled.</p>
        </div>
    @elseif($step === Order::STEP_DONE && ! $amending)
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-green-200 bg-green-50 p-5">
            <div>
                <p class="text-lg font-semibold text-green-800">Report is ready</p>
                <p class="text-sm text-green-700">All results are verified and released.</p>
                @if($hasBalance)
                    <p class="mt-1 text-sm font-medium text-red-600">
                        Balance due Rs. {{ number_format($invoice->balance) }} —
                        <a href="{{ route('lab.orders.show', $order) }}" wire:navigate class="underline">receive payment</a>
                    </p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('lab.orders.report', $order) }}" target="_blank" class="app-btn-success rounded-lg px-5 py-2.5 text-sm font-semibold">Print report</a>
                @if($canEnter)
                    <button wire:click="startAmending" class="rounded-lg border px-4 py-2.5 text-sm text-gray-700 hover:bg-white">Amend results</button>
                @endif
            </div>
        </div>
    @elseif($step === Order::STEP_COLLECT)
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-blue-200 bg-blue-50 p-5">
            <div>
                <p class="text-lg font-semibold text-blue-900">Next: collect the sample</p>
                <p class="text-sm text-blue-800">Press <span class="font-semibold">Collect sample</span> once it is taken, or simply type the results below — the sample is marked collected automatically.</p>
            </div>
            @if($canCollect)
                <button wire:click="collectSamples" class="app-btn-primary rounded-lg px-5 py-2.5 text-sm font-semibold">
                    <span wire:loading.remove wire:target="collectSamples">Collect sample</span>
                    <span wire:loading wire:target="collectSamples">Saving...</span>
                </button>
            @endif
        </div>
    @elseif($editable)
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
            @if($amending)
                <span class="font-semibold">Amending report.</span> Change the results, then press <span class="font-semibold">Finalize report</span> again.
            @elseif($canRelease)
                <span class="font-semibold">Next:</span> type the results below, then press <span class="font-semibold">Finalize report</span> to verify, release and print in one step.
            @else
                <span class="font-semibold">Next:</span> type the results below and save. A lab incharge will verify and release the report.
            @endif
        </div>
    @endif

    {{-- Tests & results --}}
    <div class="overflow-hidden rounded-xl bg-white shadow"
         x-data
         x-init="$nextTick(() => { const first = [...$el.querySelectorAll('[data-result-input]')].find(i => !i.disabled && !i.value); first && first.focus(); })">
        <div class="flex items-center justify-between border-b px-5 py-3">
            <h3 class="font-semibold text-gray-800">Tests & results</h3>
            <span class="text-xs text-gray-500">{{ $order->items->count() }} test(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-2 text-left">Test</th>
                        <th class="w-40 px-2 py-2 text-left">Result</th>
                        <th class="w-24 px-2 py-2 text-left">Unit</th>
                        <th class="w-32 px-2 py-2 text-left">Normal range</th>
                        <th class="w-28 px-2 py-2 text-left">Flag</th>
                        <th class="px-2 py-2 text-left">Remarks</th>
                        <th class="w-28 px-4 py-2 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($order->items as $item)
                        @php $flag = $entries[$item->id]['flag'] ?? Result::FLAG_NORMAL; @endphp
                        <tr wire:key="item-{{ $item->id }}" class="align-top">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $item->test->name }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $item->test->code }} · {{ $item->sample?->sample_type ?: ($item->test->sample_type ?: 'Sample') }}
                                    @if($item->sample?->isCollected())
                                        · <span class="font-mono">{{ $item->sample->accession_number }}</span>
                                    @elseif($item->sample?->status === 'rejected')
                                        · <span class="font-medium text-red-600">Sample rejected</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-2 py-2">
                                <input type="text"
                                       data-result-input
                                       wire:model.blur="entries.{{ $item->id }}.value"
                                       x-on:keydown.enter.prevent="const all = [...$el.closest('table').querySelectorAll('[data-result-input]')]; const next = all[all.indexOf($el) + 1]; next ? next.focus() : $el.blur()"
                                       @disabled(! $editable)
                                       class="w-full rounded-md border px-2 py-1.5 text-sm font-medium disabled:bg-gray-50 disabled:text-gray-700 @error('entries.'.$item->id.'.value') border-red-500 @enderror"
                                       placeholder="Value">
                                @error('entries.'.$item->id.'.value') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-2 py-2">
                                <input type="text" wire:model.blur="entries.{{ $item->id }}.unit" @disabled(! $editable)
                                       class="w-full rounded-md border px-2 py-1.5 text-sm disabled:bg-gray-50">
                            </td>
                            <td class="px-2 py-2">
                                <input type="text" wire:model.blur="entries.{{ $item->id }}.normal_range" @disabled(! $editable)
                                       class="w-full rounded-md border px-2 py-1.5 text-sm disabled:bg-gray-50">
                            </td>
                            <td class="px-2 py-2">
                                <select wire:model.live="entries.{{ $item->id }}.flag" @disabled(! $editable)
                                        class="w-full rounded-md border px-2 py-1.5 text-sm disabled:opacity-80 {{ $flagClasses[$flag] ?? '' }}">
                                    @foreach(Result::FLAGS as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-2 py-2">
                                <input type="text" wire:model.blur="entries.{{ $item->id }}.remarks" @disabled(! $editable)
                                       class="w-full rounded-md border px-2 py-1.5 text-sm disabled:bg-gray-50 @error('entries.'.$item->id.'.remarks') border-red-500 @enderror"
                                       placeholder="{{ $flag === Result::FLAG_CRITICAL ? 'Required for critical' : 'Optional' }}">
                                @error('entries.'.$item->id.'.remarks') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </td>
                            <td class="px-4 py-3">
                                @if($item->result)
                                    <x-status-badge type="result" :status="$item->result->status" />
                                @else
                                    <span class="text-xs text-gray-400">Empty</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($editable)
            <div class="flex flex-wrap items-center justify-end gap-2 border-t bg-gray-50 px-5 py-3">
                @if($amending)
                    <button wire:click="cancelAmending" class="mr-auto text-sm text-gray-600 hover:underline">Cancel editing</button>
                @endif
                <button wire:click="saveResults('draft')" wire:loading.attr="disabled" class="rounded-lg border bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    {{ $canVerify ? 'Save draft' : 'Save results' }}
                </button>
                @if($canVerify)
                    <button wire:click="saveResults('verify')" wire:loading.attr="disabled" class="rounded-lg border border-blue-600 bg-white px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50">
                        Save & verify
                    </button>
                @endif
                @if($canRelease)
                    <button wire:click="saveResults('finalize')" wire:loading.attr="disabled" class="app-btn-success rounded-lg px-5 py-2 text-sm font-semibold">
                        <span wire:loading.remove wire:target="saveResults">Finalize report</span>
                        <span wire:loading wire:target="saveResults">Saving...</span>
                    </button>
                @endif
            </div>
        @endif
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('lab.samples.index') }}" wire:navigate class="app-link-primary text-sm">&larr; Back to samples</a>
        <div class="flex gap-2">
            <a href="{{ route('lab.orders.show', $order) }}" wire:navigate class="rounded-lg border px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Order & billing</a>
            <a href="{{ route('lab.orders.create') }}" wire:navigate class="rounded-lg border px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">New order</a>
        </div>
    </div>
</div>
