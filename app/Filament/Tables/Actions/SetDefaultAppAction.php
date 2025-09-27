<?php

namespace App\Filament\Tables\Actions;

use App\Models\App;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class SetDefaultAppAction
{
    public static function make(): Action
    {
        return Action::make('setDefault')
            ->label(__('setDefaultApp'))
            ->icon('heroicon-m-check-badge')
            ->color('success')
            ->successNotificationTitle(__('Saved'))
            ->hidden(fn(App $app) => $app->id === Auth::user()?->default_app_id)
            ->action(function (App $app) {
                // 使用数据库事务确保操作的原子性
                $user = Auth::user();
                $user->default_app_id = $app->id;
                $user->save();
            });
    }
}
