<?php
namespace App\Filament\Pages\Tenancy;

use App\Filament\Resources\Apps\AppResource;
use App\Models\App;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class RegisterApp extends RegisterTenant
{
    public static function getLabel(): string
    {
        return __('Register app');
    }

    public function form(Schema $schema): Schema
    {
        return AppResource::form($schema);
    }

    protected function handleRegistration(array $data): App
    {
        $app = new App();
        $app->forceFill($data);
        $app->owner_id = Auth::id();
        $app->save();
        return $app;
    }
}
