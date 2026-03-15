<div class="max-w-2xl mx-auto">
    <form wire:submit="update" class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">Edit Patient — {{ $patient->patient_id }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name *</label>
                <input wire:model="name" type="text" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CNIC</label>
                <input wire:model="cnic" type="text" placeholder="XXXXX-XXXXXXX-X" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                @error('cnic') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                <input wire:model="phone" type="text" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gender</label>
                <select wire:model="gender" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date of Birth</label>
                <input wire:model.live="dob" type="date" max="{{ date('Y-m-d') }}" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                @error('dob') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Age</label>
                    <input wire:model="age" type="number" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unit</label>
                    <select wire:model="age_unit" class="border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="years">Yrs</option>
                        <option value="months">Mo</option>
                        <option value="days">Days</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Referred By</label>
                <input wire:model="referred_by" type="text" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                <input wire:model="email" type="email" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
                <input wire:model="address" type="text" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
        </div>
        <div class="flex gap-3 justify-end pt-2">
            <a href="{{ route('lab.patients.index') }}" wire:navigate class="border px-5 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300">Cancel</a>
            <button type="submit" class="app-btn-primary rounded-lg px-6 py-2 text-sm font-medium transition">
                <span wire:loading.remove>Update Patient</span>
                <span wire:loading>Saving...</span>
            </button>
        </div>
    </form>
</div>
