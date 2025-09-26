<?php

namespace App\Filament\Widgets;

use App\Models\App;
use App\Models\ConsumptionLog;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class StatOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    public function getStats(): array
    {
        $sql = <<<SQL
SUM(transaction_dollars) AS transaction_dollars,
SUM(refund_dollars) AS refund_dollars,
SUM(consumption_dollars) AS consumption_dollars,
SUM(transaction_count) AS transaction_count,
SUM(refund_count) AS refund_count,
SUM(consumption_count) AS consumption_count
SQL;

        $sum = App::query()
            ->selectRaw($sql)
            ->first();
        return [

            StatsOverviewWidget\Stat::make(
                label: __('Total transactions'),
                value: $sum['transaction_count'] ?? 0,
            ),
            StatsOverviewWidget\Stat::make(
                label: __('Total consumes'),
                value: $sum['consumption_count'] ?? 0,
            ),
            StatsOverviewWidget\Stat::make(
                label: __('Total refunds'),
                value: $sum['refund_count'] ?? 0,
            ),
            StatsOverviewWidget\Stat::make(
                label: __('Total transaction dollars'),
                value: $sum['transaction_dollars'] ?? 0,
            ),
            StatsOverviewWidget\Stat::make(
                label: __('Total consumes dollars'),
                value: $sum['consumption_dollars'] ?? 0,
            ),
            StatsOverviewWidget\Stat::make(
                label: __('Total refund dollars'),
                value: $sum['refund_dollars'] ?? 0,
            ),


        ];
    }
}
