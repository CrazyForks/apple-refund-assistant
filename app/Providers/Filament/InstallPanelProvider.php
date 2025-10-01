<?php

namespace App\Providers\Filament;

use App\Filament\Install\InstallWizard;
use Filament\Panel;
use Filament\PanelProvider;

class InstallPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
//        if (env('APP_INSTALLED')) {
//            return $panel->hidden();
//        }

        return $panel
            ->id('install')
            ->path('install')
            ->navigation(false)
            ->topbar(false)
            ->breadcrumbs(false)
            ->pages([
                InstallWizard::class,
            ])
            ->authMiddleware([])
            ->middleware([
                'web',
            ]);
    }
}
