<?php

namespace App\Livewire\Lab\Results;

use App\Models\OrderItem;
use App\Models\Result;
use App\Services\LabWorkflowService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class ResultIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';

    public bool $showModal = false;
    public ?int $orderItemId = null;
    public string $value = '';
    public string $unit = '';
    public string $normal_range = '';
    public string $flag = Result::FLAG_NORMAL;
    public string $remarks = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openResultEntry(int $itemId): void
    {
        abort_unless(auth()->user()->canWorkBench(), 403);

        $item = OrderItem::with(['test', 'result'])->findOrFail($itemId);
        $this->orderItemId = $item->id;
        $this->unit = $item->result?->unit ?? ($item->test->unit ?? '');
        $this->normal_range = $item->result?->normal_range ?? ($item->test->normal_range ?? '');
        $this->value = $item->result?->value ?? '';
        $this->flag = $item->result?->flag ?? Result::FLAG_NORMAL;
        $this->remarks = $item->result?->remarks ?? '';
        $this->showModal = true;
    }

    public function saveResult(): void
    {
        abort_unless(auth()->user()->canWorkBench(), 403);

        $this->validate([
            'value' => 'required|string|max:255',
            'flag' => 'required|in:normal,high,low,critical',
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            app(LabWorkflowService::class)->saveResult(OrderItem::findOrFail($this->orderItemId), auth()->user(), [
                'value' => $this->value,
                'unit' => $this->unit,
                'normal_range' => $this->normal_range,
                'flag' => $this->flag,
                'remarks' => $this->remarks,
            ]);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
            return;
        }

        $this->reset('showModal', 'orderItemId', 'value', 'unit', 'normal_range', 'flag', 'remarks');
        session()->flash('success', 'Result saved as draft.');
    }

    public function verify(int $itemId): void
    {
        abort_unless(auth()->user()->canVerifyResults(), 403);

        try {
            app(LabWorkflowService::class)->verifyResult(OrderItem::findOrFail($itemId), auth()->user());
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
            return;
        }

        session()->flash('success', 'Result verified.');
    }

    public function render()
    {
        $items = OrderItem::with(['order.patient', 'test', 'sample', 'result'])
            ->whereHas('order', fn ($query) => $query
                ->where('lab_id', auth()->user()->lab_id)
                ->when($this->search, fn ($searchQuery) => $searchQuery
                    ->where(function ($inner) {
                        $inner->where('order_number', 'like', "%{$this->search}%")
                            ->orWhereHas('patient', fn ($patientQuery) => $patientQuery->where('name', 'like', "%{$this->search}%"));
                    })))
            ->when($this->status === 'pending', fn ($query) => $query->doesntHave('result'))
            ->when($this->status === 'draft', fn ($query) => $query->whereHas('result', fn ($resultQuery) => $resultQuery->where('status', Result::STATUS_DRAFT)))
            ->when($this->status === 'verified', fn ($query) => $query->whereHas('result', fn ($resultQuery) => $resultQuery->where('status', Result::STATUS_VERIFIED)))
            ->when($this->status === 'released', fn ($query) => $query->whereHas('result', fn ($resultQuery) => $resultQuery->where('status', Result::STATUS_RELEASED)))
            ->when($this->status === 'critical', fn ($query) => $query->whereHas('result', fn ($resultQuery) => $resultQuery->where('flag', Result::FLAG_CRITICAL)))
            ->latest()
            ->paginate(15);

        return view('livewire.lab.results.index', compact('items'))
            ->layout('layouts.lab', ['title' => 'Test Results']);
    }
}