<?php

namespace App\Livewire\Lab\Samples;

use App\Models\OrderItem;
use App\Services\LabWorkflowService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class CollectionIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $onlyUrgent = false;
    public bool $showCollectModal = false;
    public ?int $orderItemId = null;
    public string $sample_type = '';
    public string $container = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCollect(int $orderItemId): void
    {
        abort_unless(auth()->user()->canCollectSamples(), 403);

        $item = OrderItem::with(['test', 'sample'])
            ->whereHas('order', fn ($query) => $query->where('lab_id', auth()->user()->lab_id))
            ->findOrFail($orderItemId);

        $this->orderItemId = $item->id;
        $this->sample_type = $item->sample?->sample_type ?? ($item->test->sample_type ?? 'General');
        $this->container = $item->sample?->container ?? '';
        $this->showCollectModal = true;
    }

    public function saveCollection(): void
    {
        abort_unless(auth()->user()->canCollectSamples(), 403);

        $this->validate([
            'sample_type' => 'required|string|max:255',
            'container' => 'nullable|string|max:255',
        ]);

        try {
            app(LabWorkflowService::class)->collectSample(
                OrderItem::whereHas('order', fn ($query) => $query->where('lab_id', auth()->user()->lab_id))->findOrFail($this->orderItemId),
                auth()->user(),
                [
                    'sample_type' => $this->sample_type,
                    'container' => $this->container,
                ]
            );
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
            return;
        }

        $this->reset('showCollectModal', 'orderItemId', 'sample_type', 'container');
        session()->flash('success', 'Sample collected and accession label generated.');
    }

    public function render()
    {
        $items = OrderItem::with(['order.patient', 'test', 'sample'])
            ->whereHas('order', function ($query) {
                $query->where('lab_id', auth()->user()->lab_id)
                    ->when($this->search, fn ($searchQuery) => $searchQuery->where(function ($inner) {
                        $inner->where('order_number', 'like', "%{$this->search}%")
                            ->orWhereHas('patient', fn ($patientQuery) => $patientQuery->where('name', 'like', "%{$this->search}%"));
                    }))
                    ->when($this->onlyUrgent, fn ($urgentQuery) => $urgentQuery->where('is_urgent', true));
            })
            ->where(function ($query) {
                $query->whereDoesntHave('sample')
                    ->orWhereHas('sample', fn ($sampleQuery) => $sampleQuery->where('status', 'rejected'));
            })
            ->latest()
            ->paginate(12);

        return view('livewire.lab.samples.collection', compact('items'))
            ->layout('layouts.lab', ['title' => 'Samples']);
    }
}