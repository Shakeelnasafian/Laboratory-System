<?php

namespace App\Livewire\Lab;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Patient;
use App\Models\Sample;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): View
    {
        $labId = auth()->user()->lab_id;
        $days = $this->dateWindow();
        $startDate = $days->first()->toDateString();

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
        $ordersTrend = $this->countSeries(
            Order::query()->where('lab_id', $labId),
            'created_at',
            $days
        );
        $patientsTrend = $this->countSeries(
            Patient::query()->where('lab_id', $labId),
            'created_at',
            $days
        );
        $pendingCollectionTrend = $this->seriesFromCollection(
            OrderItem::with(['order:id,lab_id,created_at', 'sample:id,order_item_id,status'])
                ->whereHas('order', fn (Builder $query) => $query
                    ->where('lab_id', $labId)
                    ->whereDate('created_at', '>=', $startDate))
                ->get()
                ->filter(fn (OrderItem $item) => ! $item->sample || $item->sample->status === Sample::STATUS_REJECTED),
            fn (OrderItem $item) => $item->order?->created_at?->toDateString(),
            $days
        );
        $processingTrend = $this->seriesFromCollection(
            OrderItem::with('order:id,lab_id')
                ->whereHas('order', fn (Builder $query) => $query->where('lab_id', $labId))
                ->where('status', OrderItem::STATUS_PROCESSING)
                ->where(function (Builder $query) use ($startDate) {
                    $query->whereDate('started_at', '>=', $startDate)
                        ->orWhereDate('created_at', '>=', $startDate);
                })
                ->get(),
            fn (OrderItem $item) => ($item->started_at ?? $item->created_at)?->toDateString(),
            $days
        );
        $overdueTrend = $this->countSeries(
            OrderItem::query()
                ->whereHas('order', fn (Builder $query) => $query->where('lab_id', $labId))
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->where('status', '!=', OrderItem::STATUS_COMPLETED),
            'due_at',
            $days
        );
        $completedTrend = $this->countSeries(
            OrderItem::query()
                ->whereHas('order', fn (Builder $query) => $query->where('lab_id', $labId))
                ->whereNotNull('completed_at'),
            'completed_at',
            $days
        );
        $revenueTrend = $this->sumSeries(
            Invoice::query()->where('lab_id', $labId),
            'created_at',
            'paid_amount',
            $days
        );
        $totalPatientsTrend = $this->cumulativeSeries(
            Patient::query()->where('lab_id', $labId),
            'created_at',
            $days
        );

        $statCards = [
            $this->makeStatCard(
                'today_orders',
                'Today\'s Orders',
                (string) $todayOrders,
                'Order intake across the last 7 days',
                $this->differenceLabel($ordersTrend, 'vs yesterday')
            ),
            $this->makeStatCard(
                'today_patients',
                'Today\'s Patients',
                (string) $todayPatients,
                'Registration flow across the last week',
                $this->differenceLabel($patientsTrend, 'vs yesterday')
            ),
            $this->makeStatCard(
                'pending_collection',
                'Pending Collection',
                (string) $pendingCollection,
                'Items still waiting at collection or recollect',
                $this->differenceLabel($pendingCollectionTrend, 'queue drift')
            ),
            $this->makeStatCard(
                'processing_items',
                'In Processing',
                (string) $processingItems,
                'Bench workload currently in motion',
                $this->differenceLabel($processingTrend, 'bench momentum')
            ),
            $this->makeStatCard(
                'overdue_items',
                'Overdue Items',
                (string) $overdueItems,
                'Turnaround pressure over the last 7 days',
                $this->differenceLabel($overdueTrend, 'risk change')
            ),
            $this->makeStatCard(
                'completed_items',
                'Completed Today',
                (string) $completedItemsToday,
                'Finished bench items and validated output',
                $this->differenceLabel($completedTrend, 'vs yesterday')
            ),
            $this->makeStatCard(
                'today_revenue',
                'Today\'s Revenue',
                'Rs. ' . number_format($todayRevenue),
                'Collections captured across the last week',
                $this->differenceLabel($revenueTrend, 'vs yesterday', true)
            ),
            $this->makeStatCard(
                'total_patients',
                'Total Patients',
                (string) $totalPatients,
                'Cumulative patient base over the last 7 days',
                $this->windowGrowthLabel($totalPatientsTrend)
            ),
        ];

        return view('livewire.lab.dashboard', compact(
            'recentOrders',
            'statCards'
        ))->layout('layouts.lab', ['title' => 'Dashboard']);
    }

    private function dateWindow(int $days = 7): Collection
    {
        return collect(range($days - 1, 0))
            ->map(fn (int $offset) => today()->subDays($offset));
    }

    private function countSeries(Builder $query, string $column, Collection $days): array
    {
        $raw = $query
            ->whereDate($column, '>=', $days->first()->toDateString())
            ->selectRaw("DATE({$column}) as day_key, COUNT(*) as aggregate")
            ->groupBy('day_key')
            ->pluck('aggregate', 'day_key');

        return $this->mergeSeries($days, $raw->map(fn ($value) => (int) $value)->all());
    }

    private function sumSeries(Builder $query, string $column, string $sumColumn, Collection $days): array
    {
        $raw = $query
            ->whereDate($column, '>=', $days->first()->toDateString())
            ->selectRaw("DATE({$column}) as day_key, COALESCE(SUM({$sumColumn}), 0) as aggregate")
            ->groupBy('day_key')
            ->pluck('aggregate', 'day_key');

        return $this->mergeSeries($days, $raw->map(fn ($value) => (float) $value)->all());
    }

    private function cumulativeSeries(Builder $query, string $column, Collection $days): array
    {
        $startDate = $days->first()->toDateString();
        $baseTotal = (clone $query)->whereDate($column, '<', $startDate)->count();
        $daily = $this->countSeries($query, $column, $days);
        $runningTotal = $baseTotal;

        foreach ($daily as $day => $count) {
            $runningTotal += $count;
            $daily[$day] = $runningTotal;
        }

        return $daily;
    }

    private function seriesFromCollection(Collection $items, callable $dateResolver, Collection $days): array
    {
        $grouped = $items
            ->map(fn ($item) => $dateResolver($item))
            ->filter()
            ->countBy()
            ->map(fn ($value) => (int) $value)
            ->all();

        return $this->mergeSeries($days, $grouped);
    }

    private function mergeSeries(Collection $days, array $values): array
    {
        return $days
            ->mapWithKeys(fn (Carbon $day) => [
                $day->toDateString() => (float) ($values[$day->toDateString()] ?? 0),
            ])
            ->all();
    }

    private function makeStatCard(
        string $metricKey,
        string $label,
        string $value,
        string $subtitle,
        string $trendLabel
    ): array {
        $palette = config("ui.dashboard_metrics.{$metricKey}", config('ui.theme.dashboard.fallback'));

        return [
            'label' => $label,
            'value' => $value,
            'subtitle' => $subtitle,
            'trendLabel' => $trendLabel,
            'accent' => $palette['accent'],
            'soft' => $palette['soft'],
        ];
    }

    private function differenceLabel(array $series, string $suffix, bool $currency = false): string
    {
        $values = array_values($series);
        $current = (float) ($values[count($values) - 1] ?? 0);
        $previous = (float) ($values[count($values) - 2] ?? 0);
        $difference = $current - $previous;

        if (abs($difference) < PHP_FLOAT_EPSILON) {
            return 'No change ' . $suffix;
        }

        $formatted = $currency
            ? 'Rs. ' . number_format(abs($difference))
            : number_format(abs($difference));

        return ($difference > 0 ? '+' : '-') . $formatted . ' ' . $suffix;
    }

    private function windowGrowthLabel(array $series): string
    {
        $values = array_values($series);
        $growth = (float) (($values[count($values) - 1] ?? 0) - ($values[0] ?? 0));

        if (abs($growth) < PHP_FLOAT_EPSILON) {
            return 'Stable over 7 days';
        }

        return ($growth > 0 ? '+' : '-') . number_format(abs($growth)) . ' in 7 days';
    }
}
