<?php

namespace App\Livewire\Lab\Samples;

use App\Models\Sample;
use App\Services\LabWorkflowService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class ReceiveIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showRejectModal = false;
    public ?int $sampleId = null;
    public string $rejection_reason = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function receive(int $sampleId): void
    {
        abort_unless(auth()->user()->canReceiveSamples(), 403);

        try {
            app(LabWorkflowService::class)->receiveSample(Sample::findOrFail($sampleId), auth()->user());
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
            return;
        }

        session()->flash('success', 'Sample received into the lab bench queue.');
    }

    public function openReject(int $sampleId): void
    {
        abort_unless(auth()->user()->canReceiveSamples(), 403);

        $this->sampleId = $sampleId;
        $this->rejection_reason = '';
        $this->showRejectModal = true;
    }

    public function reject(): void
    {
        abort_unless(auth()->user()->canReceiveSamples(), 403);

        $this->validate([
            'rejection_reason' => 'required|string|min:3',
        ]);

        app(LabWorkflowService::class)->rejectSample(Sample::findOrFail($this->sampleId), $this->rejection_reason);

        $this->reset('showRejectModal', 'sampleId', 'rejection_reason');
        session()->flash('success', 'Sample rejected and sent back for recollection.');
    }

    public function render()
    {
        $samples = Sample::with(['orderItem.order.patient', 'orderItem.test', 'collectedBy'])
            ->where('status', Sample::STATUS_COLLECTED)
            ->when($this->search, function ($query) {
                $query->where(function ($inner) {
                    $inner->where('accession_number', 'like', "%{$this->search}%")
                        ->orWhereHas('orderItem.order', fn ($orderQuery) => $orderQuery->where('order_number', 'like', "%{$this->search}%"))
                        ->orWhereHas('orderItem.order.patient', fn ($patientQuery) => $patientQuery->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->latest()
            ->paginate(12);

        return view('livewire.lab.samples.receive', compact('samples'))
            ->layout('layouts.lab', ['title' => 'Sample Receive']);
    }
}