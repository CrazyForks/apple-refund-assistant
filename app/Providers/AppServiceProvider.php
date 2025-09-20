<?php

namespace App\Providers;

use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Column::configureUsing(function(Column $column): void {
            $column->translateLabel();
        });
        Filter::configureUsing(function(Filter $filter): void {
            $filter->translateLabel();
        });
        Field::configureUsing(function(Field $field): void {
            $field->translateLabel();
        });
        Entry::configureUsing(function(Entry $entry): void {
            $entry->translateLabel();
        });
    }
}
