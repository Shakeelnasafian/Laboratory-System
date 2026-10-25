<?php

namespace App\Livewire\Lab\Samples;

use App\Models\Order;
use App\Models\Result;
use App\Models\Sample;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The lab work queue: one row per order's sample, grouped by the next step.
 */
class SampleIndex extends Component
{
    use WithPagination;

    public const QUEUES = [
        'all' => 'All',
        Order::STEP_COLLECT => 'Collect sample',
        Order::STEP_RESULTS => 'Enter results',
        Order::STEP_VERIFY => 'Verify',
        Order::STEP_RELEASE => 'Release',
        Order::STEP_DONE => 'Completed',
        Order::STEP_CANCELLED => 'Cancelled',
    ];

    public string $search = '';
    public string $date = '';

    #[Url]
    public string $queue = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingQueue(): void
    {
        $this->resetPage();
    }

    public function setQueue(string $queue): void
    {
        $this->queue = array_key_exists($queue, self::QUEUES) ? $queue : 'all';
        $this->resetPage();
    }

    public function render()
    {
        $base = fn () => Order::query()
            ->when($this->search, fn ($query) => $query->where(function ($inner) {
                $inner->where('order_number', 'like', "%{$this->search}%")
                    ->orWhereHas('patient', fn ($patientQuery) => $patientQuery->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->date, fn ($query) => $query->whereDate('created_at', $this->date));

        $counts = collect(self::QUEUES)
            ->keys()
            ->mapWithKeys(fn (string $queue) => [$queue => $this->applyQueue($base(), $queue)->count()])
            ->all();

        $orders = $this->applyQueue($base(), $this->queue)
            ->with(['patient', 'items.test', 'items.sample', 'items.result'])
            ->latest()
            ->paginate(15);

        return view('livewire.lab.samples.index', compact('orders', 'counts'))
            ->layout('layouts.lab', ['title' => 'Samples']);
    }

    /**
     * SQL version of Order::workflowStep(); keep the two in step.
     */
    protected function applyQueue(Builder $query, string $queue): Builder
    {
        $missingSample = fn (Builder $items) => $items->whereDoesntHave(
            'sample',
            fn (Builder $sample) => $sample->whereIn('status', [Sample::STATUS_COLLECTED, Sample::STATUS_RECEIVED])
        );
        $missingResult = fn (Builder $items) => $items->doesntHave('result');
        $resultStatus = fn (string $status) => fn (Builder $items) => $items->whereHas(
            'result',
            fn (Builder $result) => $result->where('status', $status)
        );
        $notDone = fn (Builder $q) => $q->whereHas('items', fn (Builder $items) => $items->whereDoesntHave(
            'result',
            fn (Builder $result) => $result->where('status', Result::STATUS_RELEASED)
        ));

        return match ($queue) {
            Order::STEP_CANCELLED => $query->where('status', Order::STATUS_CANCELLED),
            Order::STEP_DONE => $query->where('status', '!=', Order::STATUS_CANCELLED)
                ->has('items')
                ->whereDoesntHave('items', fn (Builder $items) => $items->whereDoesntHave(
                    'result',
                    fn (Builder $result) => $result->where('status', Result::STATUS_RELEASED)
                )),
            Order::STEP_COLLECT => $query->where('status', '!=', Order::STATUS_CANCELLED)
                ->tap($notDone)
                ->whereHas('items', $missingSample),
            Order::STEP_RESULTS => $query->where('status', '!=', Order::STATUS_CANCELLED)
                ->tap($notDone)
                ->whereDoesntHave('items', $missingSample)
                ->whereHas('items', $missingResult),
            Order::STEP_VERIFY => $query->where('status', '!=', Order::STATUS_CANCELLED)
                ->tap($notDone)
                ->whereDoesntHave('items', $missingSample)
                ->whereDoesntHave('items', $missingResult)
                ->whereHas('items', $resultStatus(Result::STATUS_DRAFT)),
            Order::STEP_RELEASE => $query->where('status', '!=', Order::STATUS_CANCELLED)
                ->tap($notDone)
                ->whereDoesntHave('items', $missingSample)
                ->whereDoesntHave('items', $missingResult)
                ->whereDoesntHave('items', $resultStatus(Result::STATUS_DRAFT)),
            default => $query,
        };
    }
}
