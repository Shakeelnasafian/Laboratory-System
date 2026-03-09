<?php

namespace App\Livewire\Lab\Samples;

use App\Models\Sample;
use App\Services\LabWorkflowService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class RejectedIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showRecollectModal = false;
    public ?int $sampleId = null;
    public string $sample_type = '';
    public string $container = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openRecollect(int $sampleId): void
    {
        abort_unless(auth()->user()->canCollectSamples(), 403);

        $sample = Sample::with('orderItem.test')->findOrFail($sampleId);
        $this->sampleId = $sampleId;
        $this->sample_type = $sample->sample_type ?: ($sample->orderItem->test->sample_type ?? 'General');
        $this->container = $sample->container ?? '';
        $this->showRecollectModal = true;
    }

    public function recollect(): void
    {
        abort_unless(auth()->user()->canCollectSamples(), 403);

        $this->validate([
            'sample_type' => 'required|string|max:255',
            'container' => 'nullable|string|max:255',
        ]);

        $sample = Sample::with('orderItem')->findOrFail($this->sampleId);

        try {
            app(LabWorkflowService::class)->collectSample($sample->orderItem, auth()->user(), [
                'sample_type' => $this->sample_type,
                'container' => $this->container,
            ]);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
            return;
        }

        $this->reset('showRecollectModal', 'sampleId', 'sample_type', 'container');
        session()->flash('success', 'Sample recollected successfully.');
    }

    public function render()
    {
        $samples = Sample::with(['orderItem.order.patient', 'orderItem.test'])
            ->where('status', Sample::STATUS_REJECTED)
            ->when($this->search, function ($query) {
                $query->where(function ($inner) {
                    $inner->where('accession_number', 'like', "%{$this->search}%")
                        ->orWhereHas('orderItem.order', fn ($orderQuery) => $orderQuery->where('order_number', 'like', "%{$this->search}%"))
                        ->orWhereHas('orderItem.order.patient', fn ($patientQuery) => $patientQuery->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->latest('updated_at')
            ->paginate(12);

        return view('livewire.lab.samples.rejected', compact('samples'))
            ->layout('layouts.lab', ['title' => 'Rejected Samples']);
    }
}