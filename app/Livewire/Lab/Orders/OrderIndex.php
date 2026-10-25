<?php

namespace App\Livewire\Lab\Orders;

use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Front desk view of orders: registration and money. Lab work on an order
 * happens on its sample page (Samples\SampleShow).
 */
class OrderIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $payment_status = '';
    public string $date = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPaymentStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $orders = Order::with(['patient', 'items.sample', 'items.result', 'invoice'])
            ->when($this->search, fn ($query) => $query->where(function ($inner) {
                $inner->where('order_number', 'like', "%{$this->search}%")
                    ->orWhereHas('patient', fn ($patientQuery) => $patientQuery->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->payment_status, fn ($query) => $query->whereHas(
                'invoice',
                fn ($invoiceQuery) => $invoiceQuery->where('payment_status', $this->payment_status)
            ))
            ->when($this->date, fn ($query) => $query->whereDate('created_at', $this->date))
            ->latest()
            ->paginate(15);

        return view('livewire.lab.orders.index', compact('orders'))
            ->layout('layouts.lab', ['title' => 'Orders']);
    }
}
