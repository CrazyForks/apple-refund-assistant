<?php

namespace App\Providers;

use App\Utils\SqlFormatUtil;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        Schema::defaultStringLength(191);

        $this->debugSql();
        $this->languageSwitch();
    }

    protected function debugSql()
    {
        if ($this->app->hasDebugModeEnabled()) {
            $id = Str::random();
            $index = 0;
            DB::listen(function (QueryExecuted $query) use ($id, &$index) {
                Log::channel('sql')?->info(SqlFormatUtil::format(sprintf('%d@%s', $index ++, $id), $query));
            });
        }
    }

    protected function languageSwitch()
    {
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['zh', 'en', 'es', 'hi', 'ar', 'pt', 'ru', 'ja', 'fr'])
                ->labels([
                    'zh' => '中文',
                    'en' => 'English',
                    'es' => 'Español',
                    'hi' => 'हिन्दी',
                    'ar' => 'العربية',
                    'pt' => 'Português',
                    'ru' => 'Русский',
                    'ja' => '日本語',
                    'fr' => 'Français',
                ])
                ->circular();
        });
    }
}
