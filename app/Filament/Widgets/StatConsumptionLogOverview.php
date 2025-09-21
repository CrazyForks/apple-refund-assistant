<?php

namespace App\Filament\Widgets;

use App\Models\ConsumptionLog;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class StatConsumptionLogOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    public function getStats(): array
    {

        return [
            StatsOverviewWidget\Stat::make(
                label: 'Today consumes',
                value: ConsumptionLog::query()
                    ->where('created_at', '>=', Carbon::today()->startOfDay())
                    ->count(),
            ),
            StatsOverviewWidget\Stat::make(
                label: 'D7 consumes',
                value: ConsumptionLog::query()
                    ->where('created_at', '>=', Carbon::now()->subWeek()->startOfDay())
                    ->count(),
            ),
            StatsOverviewWidget\Stat::make(
                label: 'D30 consumes',
                value: ConsumptionLog::query()
                    ->where('created_at', '>=', Carbon::now()->subMonth()->startOfDay())
                    ->count(),
            ),
        ];
    }
}
