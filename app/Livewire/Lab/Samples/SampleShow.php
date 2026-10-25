<?php

namespace App\Livewire\Lab\Samples;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Result;
use App\Services\LabWorkflowService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * The sample page is where the lab works an order end to end: collect the
 * sample, type all results, verify, release and print. Money lives on the
 * order page (OrderShow).
 */
class SampleShow extends Component
{
    private const RELATIONS = [
        'patient',
        'items.test',
        'items.sample',
        'items.assignedTo',
        'items.result.enteredBy',
        'items.result.verifiedBy',
        'items.result.releasedBy',
        'invoice',
        'createdBy',
    ];

    public Order $order;
    /** Result grid keyed by order item id: value, unit, normal_range, flag, remarks. */
    public array $entries = [];

    /** A released report is read-only until the user chooses to amend it. */
    public bool $amending = false;

    public ?string $notice = null;

    public function mount(Order $order): void
    {
        $this->order = $order->load(self::RELATIONS);
        $this->fillEntries();
    }

    public function updatedEntries($value, string $key): void
    {
        [$itemId, $field] = array_pad(explode('.', $key, 2), 2, null);

        if (! in_array($field, ['value', 'normal_range'], true) || ! isset($this->entries[$itemId])) {
            return;
        }

        // Critical is a clinical judgement; never auto-replace it.
        if ($this->entries[$itemId]['flag'] === Result::FLAG_CRITICAL) {
            return;
        }

        $suggested = Result::suggestFlag($this->entries[$itemId]['value'], $this->entries[$itemId]['normal_range']);

        if ($suggested) {
            $this->entries[$itemId]['flag'] = $suggested;
        }
    }

    public function collectSamples(): void
    {
        abort_unless(auth()->user()->canCollectSamples(), 403);
        $this->ensureActive();

        $count = app(LabWorkflowService::class)->collectOrderSamples($this->order, auth()->user());

        $this->reloadOrder();
        $this->notice = $count > 0 ? 'Sample marked as collected. Enter the results below.' : 'All samples were already collected.';
    }

    /**
     * Save the grid. $mode: "draft" saves only, "verify" also verifies,
     * "finalize" saves, verifies and releases the report in one go.
     */
    public function saveResults(string $mode = 'draft'): void
    {
        $user = auth()->user();

        abort_unless($user->canWorkBench(), 403);
        abort_unless(in_array($mode, ['draft', 'verify', 'finalize'], true), 400);
        abort_if($mode !== 'draft' && ! $user->canVerifyResults(), 403);
        $this->ensureActive();

        if ($this->order->allResultsReleased() && ! $this->amending) {
            return;
        }

        $this->notice = null;
        $this->validate([
            'entries' => 'array',
            'entries.*.value' => 'nullable|string|max:255',
            'entries.*.unit' => 'nullable|string|max:50',
            'entries.*.normal_range' => 'nullable|string|max:255',
            'entries.*.flag' => 'required|in:normal,high,low,critical',
            'entries.*.remarks' => 'nullable|string|max:1000',
        ]);

        $workflow = app(LabWorkflowService::class);
        $saved = 0;

        foreach ($this->order->items as $item) {
            $entry = $this->entries[$item->id] ?? null;

            if (! $entry || trim((string) $entry['value']) === '' || ! $this->entryChanged($item, $entry)) {
                continue;
            }

            try {
                $item->setRelation('order', $this->order);

                // Walk-in labs take the sample at the counter: typing a result
                // implies the sample was collected.
                if (! $item->sample?->isCollected() && $user->canCollectSamples()) {
                    $item->setRelation('sample', $workflow->collectSample($item, $user, ['sample_type' => null, 'container' => null]));
                }

                $workflow->saveResult($item, $user, $entry);
                $saved++;
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $field => $messages) {
                    $this->addError("entries.{$item->id}." . ($field === 'remarks' ? 'remarks' : 'value'), $messages[0]);
                }
            }
        }

        $this->reloadOrder();

        if ($this->getErrorBag()->isNotEmpty()) {
            $this->notice = $saved > 0 ? "{$saved} result(s) saved. Fix the highlighted rows." : null;
            return;
        }

        if ($mode === 'finalize') {
            $missing = $this->order->items->filter(fn (OrderItem $item) => ! $item->result)->count();

            if ($missing > 0) {
                $this->addError('grid', "Enter all results before finalizing ({$missing} still empty).");
                $this->notice = $saved > 0 ? "{$saved} result(s) saved as draft." : null;
                return;
            }
        }

        if ($mode !== 'draft') {
            $workflow->verifyOrderResults($this->order, $user);
            $this->reloadOrder();
        }

        if ($mode === 'finalize') {
            try {
                $workflow->releaseOrder($this->order, $user);
            } catch (ValidationException $exception) {
                $this->addError('grid', collect($exception->errors())->flatten()->first());
                return;
            }

            $this->amending = false;
            $this->reloadOrder();
            $this->notice = 'Report finalized. You can print it now.';

            return;
        }

        $this->notice = match (true) {
            $mode === 'verify' => 'Results saved and verified.',
            $saved > 0 => "{$saved} result(s) saved.",
            default => 'No changes to save.',
        };
    }

    public function startAmending(): void
    {
        abort_unless(auth()->user()->canWorkBench(), 403);
        $this->ensureActive();

        $this->amending = true;
        $this->notice = 'Editing a released report. Changed results go back to draft until you finalize again.';
    }

    public function cancelAmending(): void
    {
        $this->amending = false;
        $this->notice = null;
        $this->resetValidation();
        $this->fillEntries();
    }

    public function printReport()
    {
        abort_unless($this->order->canPrintReport(), 403);

        return redirect()->route('lab.orders.report', $this->order);
    }

    public function render()
    {
        $user = auth()->user();
        $step = $this->order->workflowStep();
        $released = $this->order->allResultsReleased();

        return view('livewire.lab.samples.show', [
            'step' => $step,
            'canCollect' => $user->canCollectSamples(),
            'canEnter' => $user->canWorkBench(),
            'canVerify' => $user->canVerifyResults(),
            'canRelease' => $user->canReleaseResults(),
            'editable' => $user->canWorkBench()
                && $step !== Order::STEP_CANCELLED
                && (! $released || $this->amending),
        ])->layout('layouts.lab', ['title' => 'Sample · ' . $this->order->patient->name]);
    }

    protected function fillEntries(): void
    {
        $this->entries = $this->order->items
            ->mapWithKeys(fn (OrderItem $item) => [$item->id => [
                'value' => (string) ($item->result?->value ?? ''),
                'unit' => (string) ($item->result?->unit ?? $item->test->unit ?? ''),
                'normal_range' => (string) ($item->result?->normal_range ?? $item->test->normal_range ?? ''),
                'flag' => $item->result?->flag ?? Result::FLAG_NORMAL,
                'remarks' => (string) ($item->result?->remarks ?? ''),
            ]])
            ->all();
    }

    protected function entryChanged(OrderItem $item, array $entry): bool
    {
        if (! $item->result) {
            return true;
        }

        foreach (['value', 'unit', 'normal_range', 'flag', 'remarks'] as $field) {
            if ((string) ($item->result->{$field} ?? '') !== (string) ($entry[$field] ?? '')) {
                return true;
            }
        }

        return false;
    }

    protected function reloadOrder(): void
    {
        $this->order = Order::query()->with(self::RELATIONS)->findOrFail($this->order->id);
    }

    protected function ensureActive(): void
    {
        abort_if($this->order->status === Order::STATUS_CANCELLED, 403, 'This order has been cancelled.');
    }
}
