<?php

namespace App\Livewire\Lab\Orders;

use App\Models\Order;
use Livewire\Component;

class OrderShow extends Component
{
    public Order $order;

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