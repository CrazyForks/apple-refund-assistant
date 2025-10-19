<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Tenancy\ConfigApp;
use App\Filament\Pages\Tenancy\RegisterApp;
use App\Models\App;
use Filament\Forms\Components\Field;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Infolists\Components\Entry;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Columns\Column;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->databaseNotifications()
            ->topNavigation()
            ->tenant(App::class)
            ->tenantRoutePrefix('apps')
            ->tenantRegistration(RegisterApp::class)
            ->tenantProfile(ConfigApp::class)
            ->profile(EditProfile::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table->defaultSort('id', 'desc');
            $table->recordActionsPosition(RecordActionsPosition::BeforeCells);
        });
        Column::configureUsing(function (Column $column): void {
            $column->translateLabel();
        });
        Filter::configureUsing(function (Filter $filter): void {
            $filter->translateLabel();
        });
        Field::configureUsing(function (Field $field): void {
            $field->translateLabel();
        });
        Entry::configureUsing(function (Entry $entry): void {
            $entry->translateLabel();
        });

        if ($this->app->environment('demo')) {
            FilamentView::registerRenderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): string => $this->getDemoLoginInfo()
            );
        }
    }

    private function getDemoLoginInfo(): string
    {
        return '
        <div style="
            margin: 20px 0;
            padding: 16px;
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(255, 154, 158, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        ">
            <div style="
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, #ff6b6b, #ff8e8e, #ffa8a8, #ffc9c9, #ffd6d6);
                background-size: 400% 100%;
                animation: gradientShift 4s ease infinite;
            "></div>
            
            <div style="
                display: flex;
                align-items: center;
                margin-bottom: 12px;
            ">
                <div style="
                    width: 10px;
                    height: 10px;
                    background: #ff6b6b;
                    border-radius: 50%;
                    margin-right: 10px;
                    animation: pulse 1.5s infinite;
                    box-shadow: 0 0 10px rgba(255, 107, 107, 0.5);
                "></div>
                <h3 style="
                    color: #d63384;
                    font-size: 15px;
                    font-weight: 700;
                    margin: 0;
                    text-shadow: 0 2px 4px rgba(214, 51, 132, 0.2);
                ">Test Account</h3>
            </div>
            
            <div style="
                background: rgba(255, 255, 255, 0.8);
                padding: 14px;
                border-radius: 12px;
                backdrop-filter: blur(15px);
                border: 1px solid rgba(255, 255, 255, 0.4);
                box-shadow: inset 0 1px 3px rgba(255, 255, 255, 0.3);
            ">
                <div style="
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #495057;
                    font-size: 13px;
                    font-family: \'Monaco\', \'Menlo\', \'Ubuntu Mono\', monospace;
                ">
                    <span style="color: #d63384; font-weight: 600;">admin@dev.com</span>
                    <span style="margin: 0 12px; color: #6c757d;">/</span>
                    <span style="color: #6c5ce7; font-weight: 600;">admin</span>
                </div>
            </div>
        </div>
        
        <style>
            @keyframes gradientShift {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
            
            @keyframes pulse {
                0%, 100% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.7; transform: scale(1.1); }
            }
            
            .demo-account:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
                transition: all 0.3s ease;
            }
        </style>';
    }
}
