<?php

namespace App\Livewire\Lab\Orders;

use App\Models\Order;
use Livewire\Component;

class OrderShow extends Component
{
    public Order $order;
    public bool $confirmingCancel = false;

    public function mount(Order $order): void
    {
        $this->order = $order->load([
            'patient',
            'items.test',
            'items.sample',
            'items.assignedTo',
            'items.result.enteredBy',
            'items.result.verifiedBy',
            'items.result.releasedBy',
            'invoice',
            'createdBy',
        ]);
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
        $this->order->refresh()->load([
            'patient',
            'items.test',
            'items.sample',
            'items.assignedTo',
            'items.result.enteredBy',
            'items.result.verifiedBy',
            'items.result.releasedBy',
            'invoice',
            'createdBy',
        ]);

        return view('livewire.lab.orders.show')
            ->layout('layouts.lab', ['title' => 'Order #' . $this->order->order_number]);
    }
}