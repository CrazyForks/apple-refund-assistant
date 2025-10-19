<?php

namespace App\Filament\Tables\Actions;

use App\Models\App;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class SetDefaultAppAction
{
    public static function make(): Action
    {
        return Action::make('setDefault')
            ->label(__('setDefaultApp'))
            ->icon('heroicon-m-check-badge')
            ->color('success')
            ->successNotificationTitle(__('Saved'))
            ->hidden(fn (App $app) => $app->id === Auth::user()?->default_app_id)
            ->action(function (App $app) {
                // Use database transaction to ensure atomicity
                $user = Auth::user();
                $user->default_app_id = $app->id;
                $user->save();
            });
    }
}
