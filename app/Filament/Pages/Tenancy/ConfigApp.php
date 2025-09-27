<?php
namespace App\Filament\Pages\Tenancy;

use App\Filament\Resources\Apps\AppResource;
use App\Filament\Tables\Actions\AppTestWebhookAction;
use App\Filament\Tables\Actions\SetDefaultAppAction;
use App\Models\App;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ConfigApp extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return __('Config app');
    }

    public function getSubheading(): ?string
    {
        return __('set app config review all settings');
    }

    protected function getHeaderActions(): array
    {
        $app = Filament::getTenant();
        return [
            DeleteAction::make()->record($app) ->successRedirectUrl(Filament::getHomeUrl()),
            SetDefaultAppAction::make()->record($app),
            AppTestWebhookAction::make()->record($app),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return AppResource::form($schema);
    }
}
