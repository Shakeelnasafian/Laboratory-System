<?php

namespace App\Livewire\Lab\Orders;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Patient;
use App\Models\Test;
use App\Services\LabWorkflowService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class OrderCreate extends Component
{
    public string $patient_search = '';
    public ?int $patient_id = null;
    public ?object $selectedPatient = null;
    public array $patientResults = [];

    public array $selectedTests = [];
    public string $test_search = '';
    public array $testResults = [];
    public string $discount = '0';
    public string $referred_by = '';
    public bool $is_urgent = false;
    public string $notes = '';

    public string $paid_amount = '0';
    public string $payment_method = 'cash';

    public function updatedPatientSearch(): void
    {
        $search = trim($this->patient_search);

        if (mb_strlen($search) < 2) {
            $this->patientResults = [];
            return;
        }

        $tokens = preg_split('/\s+/u', mb_strtolower($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $pattern = '%' . $search . '%';

        $query = Patient::query()->where(function ($query) use ($tokens, $pattern) {
            // Case-insensitive partial matches; each word can match independently.
            $query->whereRaw('name ILIKE ?', [$pattern])
                ->orWhereRaw('cnic ILIKE ?', [$pattern])
                ->orWhereRaw('phone ILIKE ?', [$pattern])
                ->orWhereRaw('patient_id ILIKE ?', [$pattern]);

            foreach ($tokens as $token) {
                $tokenPattern = '%' . $token . '%';
                $query->orWhereRaw('name ILIKE ?', [$tokenPattern]);
            }
        });

        $matches = $query
            ->orderByRaw('CASE WHEN LOWER(name) = LOWER(?) THEN 0 WHEN LOWER(name) LIKE LOWER(?) THEN 1 ELSE 2 END', [$search, $search . '%'])
            ->limit(20)
            ->get();

        // If there are no substring matches, offer typo-tolerant suggestions among
        // names beginning with the same character rather than scanning every patient.
        if ($matches->isEmpty() && mb_strlen($search) >= 3) {
            $firstCharacter = mb_substr($search, 0, 1);
            $candidates = Patient::query()
                ->whereRaw('name ILIKE ?', [$firstCharacter . '%'])
                ->orderBy('name')
                ->limit(100)
                ->get();

            $maxDistance = mb_strlen($search) >= 6 ? 2 : 1;
            $matches = $candidates->map(function (Patient $patient) use ($search) {
                $name = mb_strtolower($patient->name);
                $distance = levenshtein(mb_strtolower($search), $name);
                foreach (preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
                    $distance = min($distance, levenshtein(mb_strtolower($search), $word));
                }

                $patient->setAttribute('_search_distance', $distance);
                return $patient;
            })->filter(fn (Patient $patient) => $patient->getAttribute('_search_distance') <= $maxDistance)
                ->sortBy('_search_distance')
                ->take(6)
                ->values();
        }

        $this->patientResults = $matches->take(6)->toArray();
    }

    public function updatedTestSearch(): void
    {
        $search = trim($this->test_search);

        if (mb_strlen($search) < 2) {
            $this->testResults = [];
            return;
        }

        $pattern = '%' . $search . '%';
        $tokens = preg_split('/\s+/u', mb_strtolower($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $matches = Test::query()
            ->where('is_active', true)
            ->where(function ($query) use ($pattern, $tokens) {
                $query->whereRaw('name ILIKE ?', [$pattern])
                    ->orWhereRaw('code ILIKE ?', [$pattern]);

                foreach ($tokens as $token) {
                    $tokenPattern = '%' . $token . '%';
                    $query->orWhereRaw('name ILIKE ?', [$tokenPattern])
                        ->orWhereRaw('code ILIKE ?', [$tokenPattern]);
                }
            })
            ->orderByRaw('CASE WHEN LOWER(name) = LOWER(?) OR LOWER(code) = LOWER(?) THEN 0 WHEN LOWER(name) LIKE LOWER(?) OR LOWER(code) LIKE LOWER(?) THEN 1 ELSE 2 END', [$search, $search, $search . '%', $search . '%'])
            ->limit(20)
            ->get();

        // Test catalogs are normally small; use edit distance as a fallback for
        // common typing mistakes when no partial match is found.
        if ($matches->isEmpty() && mb_strlen($search) >= 3) {
            $maxDistance = mb_strlen($search) >= 6 ? 2 : 1;
            $matches = Test::query()
                ->where('is_active', true)
                ->get()
                ->map(function (Test $test) use ($search) {
                    $needle = mb_strtolower($search);
                    $distance = min(
                        levenshtein($needle, mb_strtolower($test->name)),
                        levenshtein($needle, mb_strtolower($test->code ?? ''))
                    );

                    foreach (preg_split('/\s+/u', mb_strtolower($test->name), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
                        $distance = min($distance, levenshtein($needle, $word));
                    }

                    $test->setAttribute('_search_distance', $distance);
                    return $test;
                })
                ->filter(fn (Test $test) => $test->getAttribute('_search_distance') <= $maxDistance)
                ->sortBy('_search_distance')
                ->take(8)
                ->values();
        }

        $this->testResults = $matches->take(8)->toArray();
    }

    public function selectPatient(int $id): void
    {
        $patient = Patient::query()->find($id);

        if (! $patient) {
            $this->addError('patient_search', 'Selected patient is no longer available.');
            return;
        }

        $this->resetValidation('patient_search');
        $this->selectedPatient = $patient;
        $this->patient_id = $patient->id;
        $this->patient_search = $patient->name;
        $this->referred_by = $patient->referred_by ?? $this->referred_by;
        $this->patientResults = [];
    }

    public function addTest(int $testId): void
    {
        if (! isset($this->selectedTests[$testId])) {
            $test = Test::query()
                ->where('is_active', true)
                ->find($testId);

            if (! $test) {
                $this->addError('test_search', 'Selected test is no longer available.');
                return;
            }

            $this->resetValidation('test_search');
            $this->selectedTests[$testId] = [
                'id' => $test->id,
                'name' => $test->name,
                'price' => $test->price,
                'turnaround_hours' => $test->turnaround_hours,
            ];
        }

        $this->test_search = '';
        $this->testResults = [];
    }

    public function removeTest(int $testId): void
    {
        unset($this->selectedTests[$testId]);
    }

    public function getSubtotalProperty(): float
    {
        return collect($this->selectedTests)->sum('price');
    }

    public function getNetAmountProperty(): float
    {
        return max(0, $this->subtotal - (float) $this->discount);
    }

    public function placeOrder(): void
    {
        $this->validate([
            'patient_id' => 'required|exists:patients,id',
            'selectedTests' => 'required|min:1',
            'discount' => 'nullable|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
        ]);

        $patient = Patient::query()->find($this->patient_id);

        if (! $patient) {
            $this->addError('patient_id', 'Selected patient is invalid.');
            return;
        }

        $testIds = $this->selectedTestIds();
        $tests = Test::query()
            ->where('is_active', true)
            ->whereIn('id', $testIds)
            ->get()
            ->keyBy('id');

        if ($tests->count() !== count($testIds)) {
            $this->addError('selectedTests', 'One or more selected tests are no longer available.');
            return;
        }

        $subtotal = (float) $tests->sum(fn (Test $test) => (float) $test->price);
        $discount = (float) $this->discount;

        if ($discount > $subtotal) {
            $this->addError('discount', 'Discount cannot exceed the subtotal.');
            return;
        }

        $netAmount = $subtotal - $discount;
        $paid = (float) $this->paid_amount;

        if ($paid > $netAmount) {
            $this->addError('paid_amount', 'Paid amount cannot exceed the net total.');
            return;
        }

        $workflow = app(LabWorkflowService::class);
        $createdOrder = DB::transaction(function () use ($discount, $netAmount, $paid, $patient, $subtotal, $tests, $workflow) {
            $order = Order::create([
                'patient_id' => $patient->id,
                'created_by' => auth()->id(),
                'status' => Order::STATUS_PENDING,
                'is_urgent' => $this->is_urgent,
                'total_amount' => $subtotal,
                'discount' => $discount,
                'net_amount' => $netAmount,
                'referred_by' => $this->referred_by,
                'notes' => $this->notes,
            ]);

            foreach ($tests as $test) {
                $item = OrderItem::create([
                    'order_id' => $order->id,
                    'test_id' => $test->id,
                    'price' => $test->price,
                    'status' => OrderItem::STATUS_PENDING,
                ]);

                $item->setRelation('order', $order);
                $item->setRelation('test', $test);
                $item->update([
                    'due_at' => $workflow->calculateDueAt($item),
                ]);
            }

            $balance = $netAmount - $paid;
            Invoice::create([
                'lab_id' => auth()->user()->lab_id,
                'order_id' => $order->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $netAmount,
                'paid_amount' => $paid,
                'balance' => $balance,
                'payment_status' => $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                'payment_method' => $this->payment_method,
            ]);

            return $order;
        });

        session()->flash('success', "Order #{$createdOrder->order_number} created successfully.");
        $this->redirect(route('lab.orders.show', $createdOrder), navigate: true);
    }

    public function render()
    {
        return view('livewire.lab.orders.create')
            ->layout('layouts.lab', ['title' => 'New Order']);
    }

    protected function selectedTestIds(): array
    {
        return collect(array_keys($this->selectedTests))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
