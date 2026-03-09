<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Result;
use App\Models\ResultRevision;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabWorkflowService
{
    public function collectSample(OrderItem $item, User $actor, array $data): Sample
    {
        return DB::transaction(function () use ($item, $actor, $data) {
            $item->loadMissing(['order', 'test', 'sample']);

            $sample = $item->sample ?? new Sample([
                'lab_id' => $item->order->lab_id,
                'order_item_id' => $item->id,
            ]);

            $sample->fill([
                'sample_type' => $data['sample_type'] ?: ($item->test->sample_type ?: 'General'),
                'container' => $data['container'] ?: null,
                'status' => Sample::STATUS_COLLECTED,
                'collected_by' => $actor->id,
                'collected_at' => now(),
                'received_by' => null,
                'received_at' => null,
                'rejection_reason' => null,
                'is_recollection' => $sample->exists && $sample->status === Sample::STATUS_REJECTED,
                'accession_number' => $sample->accession_number ?: $this->nextAccessionNumber($item->order->lab_id),
                'label_printed_at' => now(),
            ]);
            $sample->save();

            $item->forceFill([
                'status' => OrderItem::STATUS_SAMPLE_COLLECTED,
                'due_at' => $item->due_at ?: $this->calculateDueAt($item),
            ])->save();

            $this->syncOrderStatus($item->order);

            return $sample->fresh();
        });
    }

    public function receiveSample(Sample $sample, User $actor): void
    {
        DB::transaction(function () use ($sample, $actor) {
            $sample->loadMissing('orderItem.order');

            if ($sample->status !== Sample::STATUS_COLLECTED) {
                throw ValidationException::withMessages([
                    'sample' => 'Only collected samples can be received.',
                ]);
            }

            $sample->forceFill([
                'status' => Sample::STATUS_RECEIVED,
                'received_by' => $actor->id,
                'received_at' => now(),
            ])->save();

            $sample->orderItem->forceFill([
                'status' => OrderItem::STATUS_SAMPLE_COLLECTED,
                'due_at' => $sample->orderItem->due_at ?: $this->calculateDueAt($sample->orderItem),
            ])->save();

            $this->syncOrderStatus($sample->orderItem->order);
        });
    }

    public function rejectSample(Sample $sample, string $reason): void
    {
        DB::transaction(function () use ($sample, $reason) {
            $sample->loadMissing('orderItem.order');

            $sample->forceFill([
                'status' => Sample::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'received_by' => null,
                'received_at' => null,
            ])->save();

            $sample->orderItem->forceFill([
                'status' => OrderItem::STATUS_PENDING,
                'assigned_to' => null,
                'started_at' => null,
                'completed_at' => null,
                'processing_notes' => null,
            ])->save();

            $this->syncOrderStatus($sample->orderItem->order);
        });
    }

    public function assignWorklist(OrderItem $item, ?User $assignee): void
    {
        $item->forceFill([
            'assigned_to' => $assignee?->id,
            'due_at' => $item->due_at ?: $this->calculateDueAt($item),
        ])->save();
    }

    public function startProcessing(OrderItem $item, User $actor): void
    {
        $item->loadMissing(['order', 'sample']);

        if (! $item->sample?->isCollected()) {
            throw ValidationException::withMessages([
                'sample' => 'A collected sample is required before processing can start.',
            ]);
        }

        $item->forceFill([
            'assigned_to' => $item->assigned_to ?: $actor->id,
            'status' => OrderItem::STATUS_PROCESSING,
            'started_at' => $item->started_at ?: now(),
            'due_at' => $item->due_at ?: $this->calculateDueAt($item),
        ])->save();

        $this->syncOrderStatus($item->order);
    }

    public function saveResult(OrderItem $item, User $actor, array $data): Result
    {
        return DB::transaction(function () use ($item, $actor, $data) {
            $item->loadMissing(['order', 'sample', 'result']);

            if (! $item->sample?->isCollected()) {
                throw ValidationException::withMessages([
                    'sample' => 'Collect or receive the sample before entering results.',
                ]);
            }

            if (($data['flag'] ?? Result::FLAG_NORMAL) === Result::FLAG_CRITICAL && blank($data['remarks'] ?? null)) {
                throw ValidationException::withMessages([
                    'remarks' => 'Critical results require remarks.',
                ]);
            }

            if ($item->result) {
                $this->recordRevision($item->result, $actor);
            }

            $result = Result::updateOrCreate(
                ['order_item_id' => $item->id],
                [
                    'entered_by' => $actor->id,
                    'value' => $data['value'],
                    'unit' => $data['unit'] ?: null,
                    'normal_range' => $data['normal_range'] ?: null,
                    'flag' => $data['flag'] ?? Result::FLAG_NORMAL,
                    'is_abnormal' => in_array($data['flag'] ?? Result::FLAG_NORMAL, [Result::FLAG_HIGH, Result::FLAG_LOW, Result::FLAG_CRITICAL], true),
                    'remarks' => $data['remarks'] ?: null,
                    'status' => Result::STATUS_DRAFT,
                    'is_verified' => false,
                    'verified_by' => null,
                    'verified_at' => null,
                    'released_by' => null,
                    'released_at' => null,
                    'critical_alerted_at' => null,
                ]
            );

            $item->forceFill([
                'status' => OrderItem::STATUS_COMPLETED,
                'completed_at' => now(),
            ])->save();

            $this->syncOrderStatus($item->order);

            return $result;
        });
    }

    public function verifyResult(OrderItem $item, User $actor): void
    {
        $item->loadMissing(['order', 'result']);

        if (! $item->result) {
            throw ValidationException::withMessages([
                'result' => 'Enter a result before verification.',
            ]);
        }

        if ($item->result->status !== Result::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'result' => 'Only draft results can be verified.',
            ]);
        }

        $item->result->forceFill([
            'status' => Result::STATUS_VERIFIED,
            'is_verified' => true,
            'verified_by' => $actor->id,
            'verified_at' => now(),
        ])->save();

        $this->syncOrderStatus($item->order);
    }

    public function releaseOrder(Order $order, User $actor): void
    {
        $order->loadMissing('items.result');

        if (! $order->canReleaseReport()) {
            throw ValidationException::withMessages([
                'order' => 'All results must be verified before release.',
            ]);
        }

        DB::transaction(function () use ($order, $actor) {
            foreach ($order->items as $item) {
                $item->result->forceFill([
                    'status' => Result::STATUS_RELEASED,
                    'released_by' => $actor->id,
                    'released_at' => now(),
                    'critical_alerted_at' => $item->result->flag === Result::FLAG_CRITICAL
                        ? ($item->result->critical_alerted_at ?: now())
                        : null,
                ])->save();
            }

            $this->syncOrderStatus($order->fresh('items.result'));
        });
    }

    public function syncOrderStatus(Order $order): void
    {
        $order->loadMissing('items.result', 'items.sample');

        if ($order->items->isEmpty()) {
            return;
        }

        $status = Order::STATUS_PENDING;

        if ($order->items->every(fn (OrderItem $item) => $item->result?->status === Result::STATUS_RELEASED)) {
            $status = Order::STATUS_COMPLETED;
        } elseif ($order->items->contains(fn (OrderItem $item) => in_array($item->status, [OrderItem::STATUS_PROCESSING, OrderItem::STATUS_COMPLETED], true))) {
            $status = Order::STATUS_PROCESSING;
        } elseif ($order->items->every(fn (OrderItem $item) => $item->sample?->isCollected())) {
            $status = Order::STATUS_SAMPLE_COLLECTED;
        }

        $order->forceFill([
            'status' => $status,
            'collected_at' => $status !== Order::STATUS_PENDING ? ($order->collected_at ?: now()) : null,
            'completed_at' => $status === Order::STATUS_COMPLETED ? now() : null,
        ])->save();
    }

    public function calculateDueAt(OrderItem $item): Carbon
    {
        $item->loadMissing(['order', 'test']);

        return $item->order->created_at->copy()->addHours((int) $item->test->turnaround_hours);
    }

    protected function nextAccessionNumber(int $labId): string
    {
        $date = now()->format('Ymd');
        $count = Sample::withoutGlobalScope('lab')
            ->where('lab_id', $labId)
            ->whereDate('created_at', today())
            ->count() + 1;

        return 'ACC-' . $date . '-' . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }

    protected function recordRevision(Result $result, User $actor): void
    {
        if (blank($result->value) && $result->status === Result::STATUS_DRAFT) {
            return;
        }

        ResultRevision::create([
            'result_id' => $result->id,
            'revised_by' => $actor->id,
            'previous_value' => $result->value,
            'previous_unit' => $result->unit,
            'previous_normal_range' => $result->normal_range,
            'previous_is_abnormal' => $result->is_abnormal,
            'previous_flag' => $result->flag,
            'previous_remarks' => $result->remarks,
            'previous_status' => $result->status,
            'revised_at' => now(),
        ]);
    }
}