<?php

namespace App\Filament\Widgets;

use App\Models\App;
use App\Models\ConsumptionLog;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StatOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    public function getStats(): array
    {
        $tenant = Filament::getTenant();
        return [

            StatsOverviewWidget\Stat::make(
                label: __('Total transactions'),
                value: $tenant->transaction_count ?? 0,
            ),
            StatsOverviewWidget\Stat::make(
                label: __('Total consumes'),
                value: $tenant->consumption_count ?? 0,
            ),
            StatsOverviewWidget\Stat::make(
                label: __('Total refunds'),
                value: $tenant->refund_count ?? 0,
            ),
            StatsOverviewWidget\Stat::make(
                label: __('Total transaction dollars'),
                value: $tenant->transaction_dollars ?? 0,
            ),
            StatsOverviewWidget\Stat::make(
                label: __('Total consumes dollars'),
                value: $tenant->consumption_dollars ?? 0,
            ),
            StatsOverviewWidget\Stat::make(
                label: __('Total refund dollars'),
                value: $tenant->refund_dollars ?? 0,
            ),
        ];
    }
}
