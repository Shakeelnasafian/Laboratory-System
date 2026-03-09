<?php

namespace App\Livewire\Lab\Results;

use App\Models\Order;
use App\Models\Result;
use App\Services\LabWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class ReleaseIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $queue = 'ready';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function release(int $orderId): void
    {
        abort_unless(auth()->user()->canReleaseResults(), 403);

        try {
            app(LabWorkflowService::class)->releaseOrder(Order::findOrFail($orderId), auth()->user());
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
            return;
        }

        session()->flash('success', 'Report released successfully.');
    }

    public function render()
    {
        $orders = Order::query()
            ->with(['patient', 'items.test', 'items.result'])
            ->withCount([
                'items as item_count',
                'items as ready_item_count' => fn (Builder $query) => $query->whereHas('result', fn (Builder $resultQuery) => $resultQuery->whereIn('status', [Result::STATUS_VERIFIED, Result::STATUS_RELEASED])),
                'items as released_item_count' => fn (Builder $query) => $query->whereHas('result', fn (Builder $resultQuery) => $resultQuery->where('status', Result::STATUS_RELEASED)),
                'items as critical_item_count' => fn (Builder $query) => $query->whereHas('result', fn (Builder $resultQuery) => $resultQuery->where('flag', Result::FLAG_CRITICAL)),
            ])
            ->where('lab_id', auth()->user()->lab_id)
            ->when($this->search, fn (Builder $query) => $query->where(function (Builder $inner) {
                $inner->where('order_number', 'like', "%{$this->search}%")
                    ->orWhereHas('patient', fn (Builder $patientQuery) => $patientQuery->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->queue === 'ready', fn (Builder $query) => $query->havingRaw('item_count > 0 and ready_item_count = item_count and released_item_count < item_count'))
            ->when($this->queue === 'released', fn (Builder $query) => $query->havingRaw('item_count > 0 and released_item_count = item_count'))
            ->when($this->queue === 'critical', fn (Builder $query) => $query->havingRaw('critical_item_count > 0'))
            ->latest()
            ->paginate(12);

        return view('livewire.lab.results.release', compact('orders'))
            ->layout('layouts.lab', ['title' => 'Result Release']);
    }
}