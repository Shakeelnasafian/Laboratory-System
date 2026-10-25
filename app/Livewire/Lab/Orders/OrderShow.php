<?php

namespace App\Livewire\Lab\Orders;

use App\Models\Order;
use Livewire\Component;

/**
 * Front desk view of one order: what was ordered, money, cancellation.
 * Collecting and results happen on the sample page (Samples\SampleShow).
 */
class OrderShow extends Component
{
    private const RELATIONS = [
        'patient',
        'items.test',
        'items.sample',
        'items.result',
        'invoice',
        'createdBy',
    ];

    public Order $order;
    public ?string $notice = null;

    public function mount(Order $order): void
    {
        $this->order = $order->load(self::RELATIONS);
    }

    public function receiveBalance(): void
    {
        $invoice = $this->order->invoice;
        abort_unless($invoice, 404);

        $invoice->update([
            'paid_amount' => $invoice->total,
            'balance' => 0,
            'payment_status' => 'paid',
        ]);

        $this->order = Order::query()->with(self::RELATIONS)->findOrFail($this->order->id);
        $this->notice = 'Balance received. Invoice is fully paid.';
    }

    public function cancelOrder(): void
    {
        abort_unless(
            in_array($this->order->status, [Order::STATUS_PENDING, Order::STATUS_SAMPLE_COLLECTED], true),
            403,
            'This order cannot be cancelled at its current stage.'
        );

        $this->order->update(['status' => Order::STATUS_CANCELLED]);

        session()->flash('success', "Order #{$this->order->order_number} has been cancelled.");
        $this->redirect(route('lab.orders.index'), navigate: true);
    }

    public function printReport()
    {
        abort_unless($this->order->canPrintReport(), 403);

        return redirect()->route('lab.orders.report', $this->order);
    }

    public function render()
    {
        return view('livewire.lab.orders.show', ['step' => $this->order->workflowStep()])
            ->layout('layouts.lab', ['title' => 'Order #' . $this->order->order_number]);
    }
}
