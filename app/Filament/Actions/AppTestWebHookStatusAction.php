<?php

namespace App\Filament\Actions;

use App\Enums\AppStatusEnum;
use App\Models\App;
use App\Services\ApplePayService;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Log;
use Readdle\AppStoreServerAPI\Environment;

class AppTestWebHookStatusAction
{
    public static function make()
    {
        return Action::make(__('config_webhook_status'))
            ->icon('heroicon-m-exclamation-circle')
            ->color('warning')
            ->action(function (App $app, Action $action) {
                $api = ApplePayService::make($app, Environment::SANDBOX);
                $resp = $api->getTestNotificationStatus($app->test_notification_token);
            });
    }
}
