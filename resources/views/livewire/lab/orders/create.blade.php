<div class="max-w-4xl mx-auto space-y-6">

    {{-- Patient Selection --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">1. Select Patient</h2>
        @if($selectedPatient)
            <div class="flex items-center justify-between bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg">
                <div>
                    <p class="font-semibold text-gray-800 dark:text-white">{{ $selectedPatient->name }}</p>
                    <p class="text-sm text-gray-500">{{ $selectedPatient->patient_id }} &bull; {{ $selectedPatient->phone ?? 'No phone' }} &bull; {{ $selectedPatient->age ? $selectedPatient->age.' '.$selectedPatient->age_unit : '' }} {{ ucfirst($selectedPatient->gender) }}</p>
                </div>
                <button wire:click="$set('selectedPatient', null)" class="text-red-500 hover:underline text-xs">Change</button>
            </div>
        @else
            <div class="relative">
                <input wire:model.live.debounce.300ms="patient_search" type="text" placeholder="Search patient by name, CNIC, or phone..."
                       class="w-full border rounded-lg px-4 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                @if(count($patientResults) > 0)
                    <div class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        @foreach($patientResults as $p)
                            <button wire:click="selectPatient({{ $p['id'] }})" type="button"
                                    class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-sm">
                                <span class="font-medium text-gray-800 dark:text-white">{{ $p['name'] }}</span>
                                <span class="text-gray-400 ml-2">{{ $p['cnic'] ?? $p['phone'] ?? '' }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
                <p class="text-xs text-gray-400 mt-2">Can't find? <a href="{{ route('lab.patients.create') }}" wire:navigate class="app-link-primary">Register new patient</a></p>
            </div>
        @endif
    </div>

    {{-- Test Selection --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">2. Add Tests</h2>
        <div class="relative mb-4">
            <input wire:model.live.debounce.300ms="test_search" type="text" placeholder="Search test by name or code..."
                   class="w-full border rounded-lg px-4 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            @if(count($testResults) > 0)
                <div class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                    @foreach($testResults as $t)
                        <button wire:click="addTest({{ $t['id'] }})" type="button"
                                class="w-full flex justify-between px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-sm">
                            <span class="text-gray-800 dark:text-white">{{ $t['name'] }}</span>
                            <span class="text-green-600 font-medium">Rs. {{ number_format($t['price']) }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Selected Tests --}}
        @if(count($selectedTests) > 0)
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-2 text-left">Test</th>
                        <th class="px-4 py-2 text-right">Price</th>
                        <th class="px-4 py-2 text-right w-16"></th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    @foreach($selectedTests as $testId => $testData)
                    <tr>
                        <td class="px-4 py-2 text-gray-800 dark:text-white">{{ $testData['name'] }}</td>
                        <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-200">Rs. {{ number_format($testData['price']) }}</td>
                        <td class="px-4 py-2 text-right">
                            <button wire:click="removeTest({{ $testId }})" class="text-red-500 hover:underline text-xs">Remove</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-gray-400 text-sm text-center py-4">No tests added yet.</p>
        @endif
    </div>

    {{-- Order Details & Payment --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">3. Order Details</h2>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Referred By (Doctor)</label>
                    <input wire:model="referred_by" type="text" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="is_urgent" type="checkbox" id="urgent" class="rounded">
                    <label for="urgent" class="text-sm text-red-600 font-medium">Mark as Urgent</label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                    <textarea wire:model="notes" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">4. Payment</h2>
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Subtotal:</span>
                    <span class="font-medium text-gray-800 dark:text-white">Rs. {{ number_format($this->subtotal) }}</span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Discount (Rs.)</label>
                    <input wire:model.live="discount" type="number" step="0.01" min="0" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div class="flex justify-between text-sm font-bold border-t dark:border-gray-700 pt-2">
                    <span class="text-gray-700 dark:text-gray-200">Net Amount:</span>
                    <span class="text-green-600">Rs. {{ number_format($this->netAmount) }}</span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Paid Amount</label>
                    <input wire:model="paid_amount" type="number" step="0.01" min="0" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Method</label>
                    <select wire:model="payment_method" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="cash">Cash</option>
                        <option value="jazzcash">JazzCash</option>
                        <option value="easypaisa">EasyPaisa</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Submit --}}
    <div class="flex flex-wrap items-center justify-end gap-3">
        @if(auth()->user()->canCollectSamples())
            <label class="mr-auto flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input wire:model="collect_now" type="checkbox" class="rounded">
                Sample collected now <span class="text-xs text-gray-500">(go straight to results)</span>
            </label>
        @endif
        <a href="{{ route('lab.orders.index') }}" wire:navigate class="border px-6 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Cancel</a>
        <button wire:click="placeOrder"
                @if(!$patient_id || count($selectedTests) === 0) disabled @endif
                class="app-btn-primary disabled:bg-gray-400 px-8 py-2 rounded-lg text-sm font-medium transition">
            <span wire:loading.remove wire:target="placeOrder">Place Order</span>
            <span wire:loading wire:target="placeOrder">Placing...</span>
        </button>
    </div>
</div>
