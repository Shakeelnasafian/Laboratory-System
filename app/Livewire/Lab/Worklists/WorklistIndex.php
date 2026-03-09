<?php

namespace App\Livewire\Lab\Worklists;

use App\Models\OrderItem;
use App\Services\LabWorkflowService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class WorklistIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $queue = 'unassigned';
    public array $selectedItems = [];
    public array $notes = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function assignToMe(int $itemId): void
    {
        abort_unless(auth()->user()->canWorkBench(), 403);

        app(LabWorkflowService::class)->assignWorklist(
            OrderItem::whereHas('order', fn ($query) => $query->where('lab_id', auth()->user()->lab_id))->findOrFail($itemId),
            auth()->user()
        );
        session()->flash('success', 'Work item assigned to you.');
    }

    public function assignSelectedToMe(): void
    {
        abort_unless(auth()->user()->canWorkBench(), 403);

        foreach (OrderItem::whereHas('order', fn ($query) => $query->where('lab_id', auth()->user()->lab_id))->whereIn('id', $this->selectedItems)->get() as $item) {
            app(LabWorkflowService::class)->assignWorklist($item, auth()->user());
        }

        $this->selectedItems = [];
        session()->flash('success', 'Selected work items assigned to you.');
    }

    public function startProcessing(int $itemId): void
    {
        abort_unless(auth()->user()->canWorkBench(), 403);

        try {
            app(LabWorkflowService::class)->startProcessing(
                OrderItem::whereHas('order', fn ($query) => $query->where('lab_id', auth()->user()->lab_id))->findOrFail($itemId),
                auth()->user()
            );
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
            return;
        }

        session()->flash('success', 'Work item moved to processing.');
    }

    public function startSelectedProcessing(): void
    {
        abort_unless(auth()->user()->canWorkBench(), 403);

        foreach (OrderItem::whereHas('order', fn ($query) => $query->where('lab_id', auth()->user()->lab_id))->whereIn('id', $this->selectedItems)->get() as $item) {
            try {
                app(LabWorkflowService::class)->startProcessing($item, auth()->user());
            } catch (ValidationException) {
                continue;
            }
        }

        $this->selectedItems = [];
        session()->flash('success', 'Selected eligible items moved to processing.');
    }

    public function saveNotes(int $itemId): void
    {
        abort_unless(auth()->user()->canWorkBench(), 403);

        $item = OrderItem::whereHas('order', fn ($query) => $query->where('lab_id', auth()->user()->lab_id))->findOrFail($itemId);
        $item->update([
            'processing_notes' => $this->notes[$itemId] ?? null,
        ]);

        session()->flash('success', 'Processing note saved.');
    }

    public function render()
    {
        $items = OrderItem::with(['order.patient', 'test.category', 'sample', 'assignedTo', 'result'])
            ->whereHas('order', function ($query) {
                $query->where('lab_id', auth()->user()->lab_id)
                    ->when($this->search, fn ($searchQuery) => $searchQuery->where(function ($inner) {
                        $inner->where('order_number', 'like', "%{$this->search}%")
                            ->orWhereHas('patient', fn ($patientQuery) => $patientQuery->where('name', 'like', "%{$this->search}%"));
                    }));
            })
            ->whereHas('sample', fn ($query) => $query->whereIn('status', ['collected', 'received']))
            ->when($this->queue === 'unassigned', fn ($query) => $query->whereNull('assigned_to'))
            ->when($this->queue === 'mine', fn ($query) => $query->where('assigned_to', auth()->id()))
            ->when($this->queue === 'urgent', fn ($query) => $query->whereHas('order', fn ($orderQuery) => $orderQuery->where('is_urgent', true)))
            ->when($this->queue === 'overdue', fn ($query) => $query->whereNotNull('due_at')->where('due_at', '<', now())->where('status', '!=', OrderItem::STATUS_COMPLETED))
            ->orderByRaw("case when assigned_to is null then 0 else 1 end")
            ->orderBy('due_at')
            ->paginate(15);

        foreach ($items as $item) {
            $this->notes[$item->id] ??= $item->processing_notes ?? '';
        }

        return view('livewire.lab.worklists.index', compact('items'))
            ->layout('layouts.lab', ['title' => 'Bench Worklists']);
    }
}