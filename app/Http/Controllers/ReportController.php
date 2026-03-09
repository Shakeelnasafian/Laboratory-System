<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function orderReport(Order $order)
    {
        abort_unless($order->canPrintReport(), 403, 'Release the report before printing it.');

        $order->load([
            'patient',
            'items.test',
            'items.sample',
            'items.result.enteredBy',
            'items.result.verifiedBy',
            'items.result.releasedBy',
        ]);
        $lab = auth()->user()->lab;

        $pdf = Pdf::loadView('reports.order', compact('order', 'lab'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("Report-{$order->order_number}.pdf");
    }
}