<?php

namespace App\Livewire\Lab;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Patient;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $labId = auth()->user()->lab_id;

        $todayOrders = Order::where('lab_id', $labId)->whereDate('created_at', today())->count();
        $todayPatients = Patient::where('lab_id', $labId)->whereDate('created_at', today())->count();
        $todayRevenue = Invoice::where('lab_id', $labId)->whereDate('created_at', today())->sum('paid_amount');
        $totalPatients = Patient::where('lab_id', $labId)->count();
        $pendingCollection = OrderItem::whereHas('order', fn ($query) => $query->where('lab_id', $labId))
            ->where(function ($query) {
                $query->whereDoesntHave('sample')
                    ->orWhereHas('sample', fn ($sampleQuery) => $sampleQuery->where('status', 'rejected'));
            })
            ->count();
        $processingItems = OrderItem::whereHas('order', fn ($query) => $query->where('lab_id', $labId))
            ->where('status', OrderItem::STATUS_PROCESSING)
            ->count();
        $overdueItems = OrderItem::whereHas('order', fn ($query) => $query->where('lab_id', $labId))
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->where('status', '!=', OrderItem::STATUS_COMPLETED)
            ->count();
        $completedItemsToday = OrderItem::whereHas('order', fn ($query) => $query->where('lab_id', $labId))
            ->whereDate('completed_at', today())
            ->count();
        $recentOrders = Order::with(['patient', 'items.test', 'items.result'])
            ->where('lab_id', $labId)
            ->latest()
            ->take(8)
            ->get();

        return view('livewire.lab.dashboard', compact(
            'todayOrders',
            'todayPatients',
            'todayRevenue',
            'totalPatients',
            'pendingCollection',
            'processingItems',
            'overdueItems',
            'completedItemsToday',
            'recentOrders'
        ))->layout('layouts.lab', ['title' => 'Dashboard']);
    }
}