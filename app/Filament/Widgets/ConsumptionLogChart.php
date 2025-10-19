<?php

namespace App\Filament\Widgets;

use App\Enums\ConsumptionLogStatusEnum;
use App\Models\ConsumptionLog;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class ConsumptionLogChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '30';

    public function getHeading(): ?string
    {
        return __('Consumption Logs Trend');
    }

    protected function getData(): array
    {
        $tenant = Filament::getTenant();
        if (! $tenant) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $days = (int) $this->filter;
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Generate date labels
        $labels = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $labels[] = $currentDate->format('m-d');
            $currentDate->addDay();
        }

        // Query data
        $data = ConsumptionLog::query()
            ->where('app_id', $tenant->id)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->select([
                DB::raw('DATE(created_at) as date'),
                'status',
                DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();

        // Initialize data arrays
        $totalData = array_fill(0, count($labels), 0);
        $refundData = array_fill(0, count($labels), 0);
        $refundDeclinedData = array_fill(0, count($labels), 0);

        // Fill data
        foreach ($data as $item) {
            $dateKey = Carbon::parse($item->date)->format('m-d');
            $index = array_search($dateKey, $labels);

            if ($index !== false) {
                $count = $item->count;
                $totalData[$index] += $count;

                if ($item->status === ConsumptionLogStatusEnum::REFUND) {
                    $refundData[$index] = $count;
                } elseif ($item->status === ConsumptionLogStatusEnum::REFUND_DECLINED) {
                    $refundDeclinedData[$index] = $count;
                }
            }
        }

        return [
            'datasets' => [
                [
                    'label' => __('Total'),
                    'data' => $totalData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                ],
                [
                    'label' => __('Refund'),
                    'data' => $refundData,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'tension' => 0.4,
                    'fill' => false,
                ],
                [
                    'label' => __('Refund Declined'),
                    'data' => $refundDeclinedData,
                    'borderColor' => 'rgb(234, 179, 8)',
                    'backgroundColor' => 'rgba(234, 179, 8, 0.1)',
                    'tension' => 0.4,
                    'fill' => false,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            '30' => '30 '.__('days'),
            '90' => '90 '.__('days'),
            '180' => '180 '.__('days'),
        ];
    }
}
